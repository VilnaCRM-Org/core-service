# PRD: Include Configuration Validation in CI

## BMALPH Stage

- Command surface: `create-prd`

## Requirement

`make ci` must execute `make validate-configuration`.

## Functional Requirements

1. `make ci` invokes `make validate-configuration`.
2. The invocation happens before expensive validation and test targets.
3. If `make validate-configuration` fails, `make ci` records a readable failure
   in the existing aggregated failure list.
4. The standalone `make validate-configuration` target remains available and
   unchanged.
5. A repository test asserts that the `ci` target contains the validation
   invocation and failure label.

## Acceptance Criteria

1. `Makefile` includes a `make validate-configuration` call inside the `ci`
   target.
2. `Makefile` includes a failure summary entry for configuration validation.
3. `tests/CLI/bats/make_general_tests.bats` or a similarly appropriate Bats file
   checks the `ci` target integration.
4. `make validate-configuration` passes locally.
5. The targeted Bats test passes locally.

## Out of Scope

- Adding new validation rules.
- Changing CI workflows.
- Running full `make ci` as part of this small issue unless a later review asks
  for full local validation.
