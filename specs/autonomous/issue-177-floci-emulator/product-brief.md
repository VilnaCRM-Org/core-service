# Product Brief: Floci AWS Emulator Migration

## Problem

Local development and load-test workflows are still wired to LocalStack-specific service names, environment variables, storage paths, and health endpoints. This keeps the project tied to one emulator and leaves helper scripts asserting emulator internals rather than AWS API behavior.

## Users

- Backend developers running the Docker development stack.
- QA and performance engineers running local load-test workflows.
- Maintainers keeping local AWS emulation aligned with current open-source tooling.

## Goals

- Replace LocalStack Compose services with Floci.
- Use vendor-neutral `aws-emulator` naming in runtime config.
- Preserve port `4566` and SQS queue-name DSNs.
- Ensure queue URLs are resolvable from sibling containers.
- Replace LocalStack health endpoint checks with AWS API smoke checks.
- Update docs, BATS tests, and load-test configuration.

## Non-Goals

- Change production AWS configuration.
- Rewrite Symfony Messenger transport behavior.
- Add full load-test execution changes beyond local emulator wiring.
- Keep LocalStack as a compatibility fallback, because Floci compatibility for required services was verified.

## Success Criteria

- `docker compose config` renders the Floci-backed `aws-emulator` service.
- The load-test Compose stack renders with `php` depending on `aws-emulator`.
- SQS queue URLs returned by Floci use `aws-emulator`.
- SQS send works from a sibling container using the returned queue URL.
- Host smoke checks pass for SQS, S3, IAM, STS, and EC2.
- LocalStack-specific references are removed from active runtime, script, test, and docs surfaces.
