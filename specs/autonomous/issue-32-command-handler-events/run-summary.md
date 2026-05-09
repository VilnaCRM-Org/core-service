# Run Summary: Issue 32 Command Handler Events

## BMALPH Evidence

- `bmalph --version`: 2.11.0
- `make bmalph-init BMALPH_PLATFORM=codex BMALPH_DRY_RUN=true`: passed, project already initialized.
- `bmalph upgrade --force`: completed; generated wrapper drift was restored before implementation.
- `bmalph doctor`: 19 passed, all checks OK.

## Implementation Summary

Added status/type reference-data domain events and wired them into command handlers:

- `CustomerStatusCreatedEvent`
- `CustomerStatusUpdatedEvent`
- `CustomerTypeCreatedEvent`
- `CustomerTypeUpdatedEvent`

Updated handlers:

- `CreateStatusCommandHandler`
- `CreateTypeCommandHandler`
- `UpdateStatusCommandHandler`
- `UpdateTypeCommandHandler`

Added `CustomerReferenceCacheInvalidationSubscriber` so the new events have a concrete consumer and invalidate `customer.collection` plus `customer.reference` tags.

Updated `CustomerCacheInvalidationRuleCollection` to expose reference domain-event rules for the new events.

## Verification

- Syntax check for all touched PHP files: passed.
- Focused PHPUnit:
  - 22 tests
  - 111 assertions
  - passed
- Full `tests/Unit/Customer` PHPUnit suite:
  - 347 tests
  - 1207 assertions
  - passed
- Symfony test container lint:
  - passed
- `make psalm`:
  - passed, no errors found
- `make phpmd`:
  - passed, no violations
  - vendor deprecation notices were emitted by PHPMD/PDepend under PHP 8.4
- API integration tests:
  - `tests/Integration/CustomerStatusApiTest.php`
  - `tests/Integration/CustomerTypeApiTest.php`
  - 37 tests
  - 107 assertions
  - passed
- `php-cs-fixer --dry-run --diff` for touched PHP files:
  - passed, 0 fixable files
- `make validate-configuration`:
  - passed
  - warning: `.git` is a worktree file, so git modification checks were skipped
- `git diff --check`:
  - passed

## Notes

The original issue text proposed event factories and a UUID factory. Main already implements customer events by direct construction. This PR completes the missing status/type handler event publication using the architecture currently present on `main`.
