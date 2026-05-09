# Epics and Stories: Issue 158

## BMALPH Stage

- Command surface: `create-epics-stories`

## Epic 1: Wire Configuration Validation into CI

### Story 1.1: Add `validate-configuration` to `make ci`

As a contributor, I want `make ci` to run configuration validation so that local
CI catches configuration drift before a PR reaches review.

Acceptance criteria:

- `make ci` calls `make validate-configuration`.
- The step runs before composer/security/static-analysis/test work.
- A failure is recorded as `configuration validation` or equivalent in the final
  failure summary.

### Story 1.2: Add Makefile integration coverage

As a maintainer, I want a lightweight test to pin the `make ci` integration so
future Makefile changes do not remove the validation gate by accident.

Acceptance criteria:

- A Bats test inspects the `ci` target.
- The test asserts the target calls `make validate-configuration`.
- The test asserts the failure summary label is present.

### Story 1.3: Verify the change

As a reviewer, I want evidence that the target and its test pass.

Acceptance criteria:

- `make validate-configuration` passes.
- The targeted Bats test passes.
- The branch is committed and opened as a PR linked to issue #158.
