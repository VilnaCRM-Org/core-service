# Implementation Readiness: Floci AWS Emulator Migration

## Readiness

Ready for implementation.

## Dependencies

- Docker Compose is already used for local development.
- AWS CLI is available on the host for smoke validation.
- Floci `1.5.13-compat` includes AWS CLI for container healthchecks.
- Symfony Messenger already uses SQS queue-name DSNs.

## Constraints

- Keep port `4566` to avoid needless developer workflow churn.
- Keep queue-name DSNs so Messenger can auto-create queues.
- Avoid `/_localstack/health` even though Floci serves compatibility endpoints.
- Do not change production AWS behavior.

## Test Plan

- `docker compose config`
- `COMPOSE_FILE=docker-compose.yml:docker-compose.override.yml:docker-compose.load_test.override.yml docker compose config`
- `bash -n` for touched shell scripts.
- Focused BATS:
  - `make_aws_load_tests.bats` excluding the real AWS load-test path.
  - `make_general_tests.bats` service/healthcheck filters.
  - `make_bmalph_tests.bats` workspace-port filters.
- `scripts/aws-emulator-smoke.sh` against a running Floci service.
- Sibling-container SQS create/send/delete queue checks.
- Symfony test container lint.
- `make validate-configuration`.
- `git diff --check`.

## Rollout Notes

Developers with shell-level `LOCALSTACK_PORT` overrides should move to `AWS_EMULATOR_PORT`. The port value remains `4566` by default.
