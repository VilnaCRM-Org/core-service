# Epics: Floci AWS Emulator Migration

## Epic 1: Compose Migration

- Replace `localstack` service with `aws-emulator`.
- Use `floci/floci:latest-compat`.
- Replace data volume paths with `/app/data`.
- Add Docker socket mount for Docker-backed services.
- Configure Floci hostname/base URL.

## Epic 2: Environment Abstraction

- Replace `LOCALSTACK_PORT` with `AWS_EMULATOR_PORT`.
- Replace `AWS_SQS_ENDPOINT_BASE` with `AWS_SQS_ENDPOINT`.
- Update SQS Messenger DSNs to use `AWS_EMULATOR_HOST` and `AWS_EMULATOR_PORT`.
- Update Symfony SQS client endpoint wiring.

## Epic 3: Scripts And Tests

- Update load-test local mode port resolution.
- Update Makefile service startup.
- Update workspace port persistence.
- Update BATS tests for the new service and env vars.
- Add AWS emulator smoke target.

## Epic 4: Documentation

- Update advanced configuration docs.
- Update architecture docs.
- Update load-test README.
- Refresh stale issue-176 planning references so current repo docs no longer point to LocalStack.

## Epic 5: Verification

- Render Compose configs.
- Run focused BATS tests.
- Run Symfony container lint.
- Run live Floci smoke checks.
- Verify sibling-container QueueUrl behavior.
