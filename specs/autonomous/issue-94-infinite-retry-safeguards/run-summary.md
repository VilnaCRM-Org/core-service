# Run Summary: Issue 94 Infinite Retry Safeguards

## BMALPH Evidence

- `bmalph --version`: 2.11.0
- `make bmalph-init BMALPH_PLATFORM=codex BMALPH_DRY_RUN=true`: passed, project already initialized.
- `bmalph upgrade --force`: completed; generated wrapper drift was restored before implementation.
- `bmalph doctor`: 19 passed, all checks OK.

## Implementation Summary

Updated `InfiniteRetryStrategy` to classify permanent failures and preserve infinite retry for transient or unknown failures.

Added permanent-failure routing:

- `failed-domain-events` now has `failure_transport: dead-domain-events`.
- `dead-domain-events` is configured as a terminal transport.
- `.env` and `.env.test` define the new DLQ DSN.

Added observability:

- `RetryAttemptMetric`
- `DlqRoutingMetric`
- `RetryStrategyMetricDimensions`

Added retry strategy documentation in `src/Shared/Infrastructure/RetryStrategy/README.md`.

## Verification

- Syntax check for touched PHP files: passed.
- Focused PHPUnit:
  - 20 tests
  - 147 assertions
  - passed
- Symfony test container lint:
  - passed
- `php-cs-fixer --dry-run --diff` for touched PHP files:
  - passed, 0 fixable files
- Psalm:
  - passed, no errors found
- PHPMD:
  - passed, no violations
  - vendor deprecation notices were emitted by PHPMD/PDepend under PHP 8.4
- `make validate-configuration`:
  - passed
  - warning: `.git` is a worktree file, so git modification checks were skipped
- `git diff --check`:
  - passed

## Notes

The issue listed circuit breaker safeguards as optional. This PR does not add a circuit breaker because selective permanent-failure routing and terminal DLQ support address the poison-message failure mode directly while keeping the change narrowly scoped.
