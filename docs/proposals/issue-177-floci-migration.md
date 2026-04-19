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

| Service | Why this repo needs it locally                                              | Current touch points                                            | Floci claims |
| ------- | --------------------------------------------------------------------------- | --------------------------------------------------------------- | ------------ |
| SQS     | Symfony Messenger async domain events and workers                           | `config/packages/messenger.yaml`, `config/services.yaml`        | Supported    |
| S3      | local-mode AWS helper scripts for load-test artifacts and bucket operations | `tests/Load/*.sh`                                               | Supported    |
| IAM     | helper scripts create roles, policies, and instance profiles                | `tests/Load/aws-execute-load-tests.sh`, `tests/Load/cleanup.sh` | Supported    |
| STS     | helper scripts and auth validation                                          | `tests/Load/aws-execute-load-tests.sh`, `tests/Load/cleanup.sh` | Supported    |
| EC2     | helper scripts launch and inspect load-test infrastructure in local mode    | `tests/Load/aws-execute-load-tests.sh`, `tests/Load/cleanup.sh` | Supported    |

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

## Proposed Rollout

### Phase 1: Inventory and Abstraction

- replace LocalStack-specific config naming with emulator-neutral naming
- isolate all vendor-specific behavior behind a small set of compose env vars and helper functions
- update docs so they describe a local AWS emulator, not one implementation

### Phase 2: Compose Migration

- replace the `localstack` service with a `floci` service in dev and load-test compose files
- set `FLOCI_HOSTNAME=floci`
- preserve port `4566`
- choose Floci storage mode intentionally instead of relying on defaults

### Phase 3: Runtime Validation

Validate these workflows against Floci:

- Messenger can publish domain-event messages
- worker can consume those messages
- local helper scripts can perform S3, IAM, STS, and EC2 operations they currently expect
- load-test setup logic still resolves the correct emulator endpoint and credentials

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
- subtle IAM or EC2 parity gaps in helper-script flows
- hardcoded service names in tests
- hidden assumptions around LocalStack-specific health endpoints

## Acceptance Scope for the Future Implementation

The implementation that closes `#177` should prove:

- Floci can replace LocalStack for the local AWS services we actually use
- app-side SQS publish/consume works in Docker Compose
- local helper scripts are either validated against Floci or explicitly feature-gated
- LocalStack-specific health probes and docs are removed
- the repository no longer depends on vendor-specific naming for local AWS emulation
- `make ci` passes after the migration work
