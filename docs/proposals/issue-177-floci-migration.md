# Issue 177: Floci Migration Plan

## Summary

`#177` tracks replacement of LocalStack with Floci for local AWS emulation, but only where compatibility is actually sufficient for this repository. The target state is an AWS-emulator abstraction that prefers Floci, validates the exact local service surface we use, and keeps a compatibility fallback if any required workflow is still blocked.

## Current Local AWS Footprint

The repository is already AWS-native in local development, but the implementation is vendor-specific:

- `docker-compose.override.yml` starts a `localstack` container with `sqs,ec2,s3,iam,sts`
- `docker-compose.load_test.override.yml` starts `localstack` for `sqs`
- `config/services.yaml` wires the SQS client to `AWS_SQS_ENDPOINT_BASE` plus `LOCALSTACK_PORT`
- `tests/Load/config.sh` resolves the `localstack` service port and rewrites `aws --endpoint-url`
- bats tests and compose health checks assert against `/_localstack/health`
- docs still describe local AWS access as LocalStack-specific

Relevant files:

- `docker-compose.override.yml`
- `docker-compose.load_test.override.yml`
- `config/services.yaml`
- `tests/Load/config.sh`
- `docs/advanced-configuration.md`
- `tests/CLI/bats/make_aws_load_tests.bats`
- `tests/CLI/bats/make_general_tests.bats`

## Why Floci Is a Candidate

According to Floci's current official README and website:

- it exposes the same single AWS endpoint on port `4566`
- it is positioned as a drop-in AWS wire-protocol emulator
- it documents `FLOCI_HOSTNAME` for correct SQS QueueUrl generation in multi-container Docker Compose setups
- it currently lists support for `SQS`, `S3`, `IAM`, `STS`, and `EC2`, which covers the local services we rely on today

Primary references:

- Floci README: <https://github.com/floci-io/floci/blob/main/README.md>
- Floci site: <https://floci.io/>

This is enough to justify a migration attempt. It is not enough to justify blind replacement without repository-specific compatibility checks.

## Required Local Service Matrix

The migration should validate the exact surface we use locally:

| Service | Why this repo needs it locally | Current touch points | Floci claims |
| --- | --- | --- | --- |
| SQS | Symfony Messenger async domain events and workers | `config/packages/messenger.yaml`, `config/services.yaml` | Supported |
| S3 | local-mode AWS helper scripts for load-test artifacts and bucket operations | `tests/Load/*.sh` | Supported |
| IAM | helper scripts create roles, policies, and instance profiles | `tests/Load/aws-execute-load-tests.sh`, `tests/Load/cleanup.sh` | Partial |
| STS | helper scripts and auth validation | `tests/Load/aws-execute-load-tests.sh`, `tests/Load/cleanup.sh` | Partial |
| EC2 | helper scripts launch and inspect load-test infrastructure in local mode | `tests/Load/aws-execute-load-tests.sh`, `tests/Load/cleanup.sh` | Partial |

Working assumptions before the migration starts:

- `IAM` must be treated as partial until we confirm the exact policy-evaluation branches used by the helper scripts, plus instance-profile creation and attachment behavior.
- `STS` must be treated as partial until we verify the repository's `AssumeRole`-style flows and SigV4 request-signing behavior end to end.
- `EC2` must be treated as partial until we verify instance lifecycle transitions, describe/list semantics, and tag handling against the local helper scripts.

## Migration Principles

### 1. Prefer an Emulator Abstraction, Not a Vendor Rename

Do not simply rename `localstack` to `floci` in one file. The repository should stop encoding emulator-specific assumptions in:

- service names
- env-var names
- health endpoints
- Bats assertions
- docs wording

Suggested abstraction:

- `AWS_EMULATOR_HOST`
- `AWS_EMULATOR_PORT`
- `AWS_EMULATOR_PROVIDER`

Backward compatibility can keep `LOCALSTACK_PORT` as a deprecated alias during transition.

### 2. Remove Vendor-Specific Health Checks

The current compose files depend on `/_localstack/health`, which is not portable. That should be replaced with capability checks that prove the APIs we actually need are working.

Examples:

- SQS smoke check by creating or listing a queue
- STS smoke check by calling `GetCallerIdentity`
- S3 smoke check by listing or creating a test bucket

These checks belong in scripts or startup smoke tests, not in vendor-internal HTTP paths.

### 3. Handle Queue URL Hostnames Correctly

Floci's documented `FLOCI_HOSTNAME` support matters for this repo because Symfony Messenger and worker containers need queue URLs that resolve from inside the Docker network, not `localhost`.

The migration should explicitly test:

- queue creation from one container
- queue consumption from another container
- QueueUrl values returned by the emulator

### 4. Pin a Version

The implementation should pin a concrete Floci release after compatibility validation. Do not use `latest` in the repository.

## Phase 0 Blocker: Verify the Actual Usage Surface

Do not enter Phase 1 until the repository's current LocalStack usage is mapped to concrete commands and expectations.

Minimum blocker checklist:

- confirm whether any load-test or helper-script path relies on STS role assumption rather than simple static credentials
- confirm which IAM APIs are required beyond create/list primitives, especially policy evaluation and instance-profile behavior
- confirm which EC2 flows are exercised locally, including instance lifecycle transitions, waiters, and tagging
- document the expected QueueUrl hostname shape that PHP, workers, and helper scripts currently consume

## Proposed Rollout

### Phase 1: Inventory and Abstraction

- replace LocalStack-specific config naming with emulator-neutral naming
- isolate all vendor-specific behavior behind a small set of compose env vars and helper functions
- update docs so they describe a local AWS emulator, not one implementation
- run a repo-wide `rg` inventory for `localstack`, `LOCALSTACK_PORT`, `/_localstack/health`, and hardcoded `4566` assumptions before any rename lands

#### Abstraction implementation

Phase 1 should introduce a compatibility wrapper instead of a one-shot rename:

- add `AWS_EMULATOR_HOST`, `AWS_EMULATOR_PORT`, and `AWS_EMULATOR_PROVIDER` as the new configuration surface
- keep `LOCALSTACK_PORT` as a deprecated alias only during the transition, with a visible runtime warning whenever it is present in app startup scripts, Bats helpers, or load-test bootstrap
- default `AWS_EMULATOR_PROVIDER` to `localstack` when unset in the transition release so existing developer setups do not break mid-migration
- validate `AWS_EMULATOR_PROVIDER` against known values `localstack` and `floci`; if another string is supplied, warn loudly, preserve host/port-driven behavior, and treat it as a custom emulator provider rather than failing silently
- publish a removal timeline in the migration guide: remove `LOCALSTACK_PORT` no earlier than two releases or sixty days after Floci becomes the default provider, whichever is longer

Example transition shape:

Before:

```yaml
services:
  localstack:
    image: localstack/localstack:3.4.0
    ports:
      - "${LOCALSTACK_PORT}:4566"
```

```bash
export LOCALSTACK_PORT="${LOCALSTACK_PORT:-4566}"
ENDPOINT_URL="http://localhost:${LOCALSTACK_PORT}"
docker compose port localstack 4566
```

After:

```yaml
services:
  floci:
    image: hectorvent/floci:<pinned-version>
    ports:
      - "${AWS_EMULATOR_PORT:-4566}:4566"
    environment:
      AWS_EMULATOR_PROVIDER: "${AWS_EMULATOR_PROVIDER:-localstack}"
      FLOCI_HOSTNAME: floci
      FLOCI_STORAGE_MODE: hybrid
```

```bash
export AWS_EMULATOR_PROVIDER="${AWS_EMULATOR_PROVIDER:-localstack}"
export AWS_EMULATOR_HOST="${AWS_EMULATOR_HOST:-localhost}"
export AWS_EMULATOR_PORT="${AWS_EMULATOR_PORT:-${LOCALSTACK_PORT:-4566}}"

if [[ -n "${LOCALSTACK_PORT:-}" ]]; then
  echo "warning: LOCALSTACK_PORT is deprecated; use AWS_EMULATOR_PORT" >&2
fi

ENDPOINT_URL="http://${AWS_EMULATOR_HOST}:${AWS_EMULATOR_PORT}"
docker compose port "${AWS_EMULATOR_PROVIDER}" 4566
```

### Phase 2: Compose Migration

- replace the `localstack` service with a `floci` service in dev and load-test compose files
- set `FLOCI_HOSTNAME=floci`
- preserve port `4566`
- choose Floci storage mode intentionally instead of relying on defaults

#### Storage mode guidance

Floci currently documents four storage modes:

- `memory` for the fastest ephemeral execution
- `persistent` for fully synchronous durable writes
- `hybrid` for in-memory reads with asynchronous persistence
- `wal` for append-only write-heavy workloads with compaction

For this repository, Phase 2 should pin `FLOCI_STORAGE_MODE=hybrid` in Docker Compose because local development benefits from restart persistence, while the Floci docs recommend `hybrid` specifically for local development and reserve `memory` for fast CI-style execution. The compose defaults should therefore be deterministic:

- `FLOCI_HOSTNAME=floci`
- `FLOCI_STORAGE_MODE=hybrid`
- `FLOCI_STORAGE_PERSISTENT_PATH=/app/data`
- published port `4566`

If CI later migrates to Floci, the GitHub Actions variant can explicitly override the mode to `memory` to minimize job runtime and keep the local-developer profile separate from ephemeral CI workers.

### Phase 3: Runtime Validation

Validate these workflows against Floci with both automated and developer-run checks:

- add a dedicated emulator-validation script such as `scripts/validate-floci.sh`
- run that script locally before merge and record the outcome in a committed validation artifact such as `docs/floci-validation-results.md`, plus a matching PR checklist item
- add a GitHub Actions job, or a provider matrix for the existing load-test/cache workflows, that boots Floci and executes the same validation set in CI

Required validation coverage:

- Messenger can publish domain-event messages to SQS
- worker can consume those messages from a separate container
- local helper scripts can perform the S3, IAM, STS, and EC2 operations they currently expect
- load-test setup logic still resolves the correct emulator endpoint, credentials, and QueueUrl hostnames
- any LocalStack-specific health probe has been replaced by API-level smoke checks

Suggested `scripts/validate-floci.sh` responsibilities:

- start the compose stack with `AWS_EMULATOR_PROVIDER=floci`
- assert `GetCallerIdentity`, queue create/list/send/receive, and a minimal S3 bucket cycle
- run the helper-script subset that exercises IAM, STS, and EC2 expectations
- fail fast if QueueUrls do not contain `floci` inside the Docker network

#### Developer transition plan

- keep a provider toggle during rollout so developers can switch between `localstack` and `floci` while compatibility is still being proven
- land a migration guide in the same change set that closes `#177`, covering new env vars, the deprecation window, and the validation script
- define rollback steps up front: set `AWS_EMULATOR_PROVIDER=localstack`, keep the deprecated alias working during the window, and leave the old compose wiring available until the Floci validation artifact is green

#### CI/CD note

Today, GitHub Actions already boots `localstack` explicitly in `.github/workflows/load-tests.yml` and `.github/workflows/cache-performance-tests.yml`. The migration therefore needs a CI decision, not just a local compose change:

- either migrate those workflows in the same PR with a provider matrix or explicit Floci jobs
- or keep them on LocalStack temporarily while the application/runtime abstraction is introduced

That choice must be called out in the rollout so `make ci` expectations and GitHub Actions behavior do not drift apart.

### Phase 4: Fallback Decision

If all required workflows pass, remove LocalStack-specific code entirely.

If any required workflow fails, keep a provider toggle and document the exact incompatibility so the repo does not regress local development.

## Expected Code Changes

The implementation that eventually closes `#177` will likely touch:

- `docker-compose.override.yml`
- `docker-compose.load_test.override.yml`
- `config/services.yaml`
- `tests/Load/config.sh`
- `tests/Load/README.md`
- `docs/advanced-configuration.md`
- `tests/CLI/bats/make_aws_load_tests.bats`
- `tests/CLI/bats/make_general_tests.bats`
- any startup smoke checks that still assume `localstack`

## Risks

- QueueUrl hostname mismatches across containers
  Mitigation: validate QueueUrls in Phase 3 with `FLOCI_HOSTNAME=floci` and fail the rollout if any helper or worker still sees `localhost`.
- subtle IAM or EC2 parity gaps in helper-script flows
  Mitigation: keep IAM, STS, and EC2 in the Phase 3 integration-validation set and do not remove the provider toggle until those checks are green.
- hardcoded service names in tests
  Mitigation: run the Phase 1 `rg` scan before code changes and parameterize every `localstack` service reference found in Bats, shell scripts, compose commands, and docs.
- hidden assumptions around LocalStack-specific health endpoints
  Mitigation: add a code-review checklist item that rejects `/_localstack/health` probes and requires API-level readiness checks instead.

## Acceptance Scope for the Future Implementation

The implementation that closes `#177` should prove:

- Floci can replace LocalStack for the local AWS services we actually use
- app-side SQS publish/consume works in Docker Compose
- local helper scripts are either validated against Floci or explicitly feature-gated
- LocalStack-specific health probes and docs are removed
- the repository no longer depends on vendor-specific naming for local AWS emulation
- the Floci migration plan explicitly states whether the GitHub Actions workflows that currently boot `localstack` are migrated in parallel, run in a provider matrix, or stay temporarily on LocalStack
- `make ci` passes after the migration work, with any required CI-pipeline adjustments called out in the migration guide
