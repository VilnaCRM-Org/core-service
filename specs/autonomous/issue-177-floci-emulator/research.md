# Research: Issue 177 Floci AWS Emulator Migration

## Source

- GitHub issue: VilnaCRM-Org/core-service#177, "Replace LocalStack with Floci for local AWS emulation where compatibility is sufficient"
- Base branch: `origin/main` at `3f3fed0393e7ccd7be9fba85eeb499237226715c`
- Worktree: `/home/kravtsov/Projects/core-service-issue-177`
- Branch: `feat/issue-177-floci-emulator`

## Primary References

- Floci README: https://github.com/floci-io/floci/blob/main/README.md
- Floci migration guide: https://floci.io/floci/getting-started/migrate-from-localstack/

## Current State On Main

Main used a `localstack` Compose service with `localstack/localstack:3.4.0`, `SERVICES=...`, `LOCALSTACK_PORT`, and healthchecks against `/_localstack/health`.

Application SQS client configuration was tied to:

- `AWS_SQS_ENDPOINT_BASE`
- `LOCALSTACK_PORT`

Load-test scripts resolved host ports through `docker compose port localstack 4566`.

Docs and BATS tests described local AWS emulation as LocalStack-specific.

## Floci Compatibility Findings

Current Floci docs state:

- The Docker image is `floci/floci`.
- Port `4566` remains the local AWS endpoint.
- `latest-compat` includes AWS CLI and boto3.
- `FLOCI_HOSTNAME` controls hostnames embedded in response URLs.
- `SERVICES` is not needed because Floci starts supported services without selection.
- Supported services include SQS, S3, IAM, STS, and EC2.

## Decision

Use a vendor-neutral Compose service named `aws-emulator` backed by `floci/floci:latest-compat`.

The compat image allows the Compose healthcheck to use `aws sqs list-queues`, which proves the SQS API is usable instead of checking an emulator-specific status endpoint.

Set both `FLOCI_HOSTNAME=aws-emulator` and `FLOCI_BASE_URL=http://aws-emulator:4566` so queue URLs returned to sibling containers resolve inside the Docker network.

Replace LocalStack-specific environment names with:

- `AWS_EMULATOR_HOST`
- `AWS_EMULATOR_PORT`
- `AWS_SQS_ENDPOINT`

Add a host-side smoke script for SQS, S3, IAM, STS, and EC2 to cover helper-script compatibility.
