# Research: Issue 158 Validate Configuration in CI

## BMALPH Stage

- Command surface: `analyst`
- Issue: <https://github.com/VilnaCRM-Org/core-service/issues/158>
- Scope: make `make ci` execute the existing `validate-configuration` target.

## Current State

`Makefile` already defines `validate-configuration` as a standalone target:

```make
validate-configuration: ## Validate configuration structure and detect locked file modifications
	@./scripts/validate-configuration.sh
```

The `ci` target runs composer validation, Symfony requirements, security checks,
style, static analysis, architecture validation, test suites, mutation testing,
and OpenAPI validation, but it does not call `make validate-configuration`.

The validation script checks repository configuration structure and locked-file
modifications. It is therefore a preflight/static-analysis style gate and should
run before costlier checks.

## Relevant Files

- `Makefile`
- `scripts/validate-configuration.sh`
- `tests/CLI/bats/make_general_tests.bats`
- `tests/CLI/bats/make_static_analyzes_tests.bats`

## Constraints

- Keep the change narrow; do not alter the validation script behavior.
- Preserve the existing `ci` failure aggregation pattern.
- Add coverage that proves the `ci` target invokes `validate-configuration`.
- Avoid running the full `make ci` locally just to prove target ordering; use
  structural Bats coverage for the Makefile and run the standalone target.

## Findings

The safest implementation is to add a new early `ci` step immediately after the
initial banner and before composer validation:

```make
echo "1... Validating repository configuration..."
if ! make validate-configuration; then failed_checks="..."
```

This keeps configuration drift detection near the beginning of CI while
preserving all later checks.

## Risks

- If `validate-configuration` has side effects or assumes a fully booted Docker
  stack, placing it early could introduce setup coupling. The script is
  standalone shell and should not require Docker.
- Existing numbered output will shift. The least risky approach is to insert a
  new first step and renumber the later messages for readability.
