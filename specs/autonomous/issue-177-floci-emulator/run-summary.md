# Run Summary: Issue 177 Floci AWS Emulator Migration

## BMALPH Evidence

- `bmalph --version`: 2.11.0
- `make bmalph-init BMALPH_PLATFORM=codex BMALPH_DRY_RUN=true`: passed, project already initialized.
- `bmalph upgrade --force`: completed; generated wrapper drift was restored before implementation.
- `bmalph doctor`: 19 passed, all checks OK.

## Implementation Summary

Replaced the local AWS emulator wiring:

- `localstack` Compose service -> `aws-emulator`.
- `localstack/localstack:3.4.0` -> `floci/floci:1.5.13-compat`.
- `LOCALSTACK_PORT` -> `AWS_EMULATOR_PORT`.
- `AWS_SQS_ENDPOINT_BASE` -> `AWS_SQS_ENDPOINT`.
- LocalStack health endpoint checks -> AWS CLI SQS `list-queues` checks.

Added `scripts/aws-emulator-smoke.sh` and `make aws-emulator-smoke` to validate SQS, S3, IAM, STS, and EC2 against the local emulator.

Updated docs and BATS tests to use vendor-neutral AWS emulator naming.

## Verification

- Official Floci docs checked:
  - `floci/floci` compatibility image tags.
  - port `4566` compatibility.
  - `FLOCI_HOSTNAME` migration mapping.
  - SQS, S3, IAM, STS, and EC2 support.
- `docker compose config`: passed.
- Load-test Compose render: passed.
- `bash -n` for touched shell scripts: passed.
- Focused BATS:
  - `make_aws_load_tests.bats` filtered subset: 7 tests passed.
  - `make_general_tests.bats` filtered subset: 2 tests passed.
  - `make_bmalph_tests.bats` filtered subset: 5 tests passed.
- Live Floci smoke:
  - `scripts/aws-emulator-smoke.sh` passed for SQS, S3, IAM, STS, and EC2 at `http://localhost:4566`.
- Sibling-container QueueUrl verification:
  - `create-queue` returned `http://aws-emulator:4566/000000000000/codex-sibling-smoke`.
  - `send-message` to that QueueUrl from a sibling container passed.
  - test queue was deleted.
- Symfony test container lint: passed.
- `make validate-configuration`: passed with expected worktree `.git` warning.
- `git diff --check`: passed.

## Notes

The full `make_aws_load_tests.bats` file was not left running because its final test executes the real AWS load-test workflow when `aws` is installed locally. The targeted assertions for this migration were run separately, and the live emulator API checks were verified directly.
