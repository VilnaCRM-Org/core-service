# Architecture: CI Configuration Validation Hook

## BMALPH Stage

- Command surface: `create-architecture`

## Design

The change stays inside the existing Makefile orchestration:

```text
make ci
  -> initialize failed_checks accumulator
  -> make validate-configuration
       -> scripts/validate-configuration.sh
  -> existing CI checks
  -> aggregate and report failures
```

## Integration Point

The `ci` target already has a shell block with repeated checks:

```make
if ! make some-target; then failed_checks="..."
```

`validate-configuration` should use the same pattern so behavior remains
consistent with all other checks.

## Failure Behavior

If configuration validation fails:

- the `ci` target continues running subsequent checks;
- `failed_checks` gets a configuration validation entry;
- the final summary exits non-zero.

This matches the current aggregate-CI design.

## Test Strategy

Use Bats to inspect the `ci` target block rather than executing all CI steps.
This verifies the intended Makefile wiring without requiring Docker, mutation
testing, or long-running suites.
