# Product Brief Distillate: Floci AWS Emulator Migration

## Core Outcome

Local AWS emulation should be provider-neutral in repository naming and backed by Floci for the services this project uses locally.

## Minimal Viable Change

- Rename the Compose service to `aws-emulator`.
- Use `floci/floci:latest-compat`.
- Replace `LOCALSTACK_PORT` with `AWS_EMULATOR_PORT`.
- Replace `AWS_SQS_ENDPOINT_BASE` with `AWS_SQS_ENDPOINT`.
- Use AWS CLI smoke healthchecks instead of `/_localstack/health`.
- Update load-test port resolution and BATS assertions.
- Document the Floci-backed local emulator.

## Risk Controls

- Keep port `4566`.
- Keep SQS queue-name DSN shape.
- Verify Floci services with AWS CLI against the running emulator.
- Verify sibling-container queue URL behavior.
- Keep changes scoped to local/dev/test configuration and docs.
