# PRD: Floci AWS Emulator Migration

## Functional Requirements

1. Development Compose must run a Floci-backed AWS emulator on port `4566`.
2. Load-test Compose must run the same AWS emulator service and make PHP wait for it.
3. Healthchecks must prove AWS API readiness, not emulator-specific status endpoints.
4. Runtime SQS configuration must use vendor-neutral endpoint variables.
5. Load-test local mode must resolve the published `aws-emulator` port.
6. Workspace bootstrap must preserve `AWS_EMULATOR_PORT`.
7. Docs must describe the local emulator as Floci-backed AWS emulation.
8. BATS tests must assert the new service, env var, and healthcheck behavior.
9. A smoke command must validate SQS, S3, IAM, STS, and EC2.

## Configuration Requirements

- `AWS_EMULATOR_HOST=aws-emulator`
- `AWS_EMULATOR_PORT=4566`
- `AWS_SQS_ENDPOINT=http://${AWS_EMULATOR_HOST}:${AWS_EMULATOR_PORT}`
- SQS Messenger DSNs use `sqs://${AWS_EMULATOR_HOST}:${AWS_EMULATOR_PORT}/...`

## Compose Requirements

- Service name: `aws-emulator`
- Image: `floci/floci:1.5.13-compat`
- `FLOCI_HOSTNAME=aws-emulator`
- `FLOCI_BASE_URL=http://aws-emulator:4566`
- Data stored under `/app/data`.

## Verification Requirements

- Dev and load-test `docker compose config` pass.
- `scripts/aws-emulator-smoke.sh` passes against a running Floci container.
- SQS queue URLs use `http://aws-emulator:4566/...`.
- SQS send works from a sibling container on the Compose network.
- Symfony test container lint passes.
- Focused BATS coverage passes for load config, Compose checks, Makefile checks, and workspace port handling.
