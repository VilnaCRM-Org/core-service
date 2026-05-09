# Product Brief: Run Configuration Validation in `make ci`

## BMALPH Stage

- Command surface: `create-brief`

## Problem

`make validate-configuration` catches locked configuration changes and
configuration-structure problems, but `make ci` does not run it. Developers can
therefore get a green local CI run while missing a configuration validation
failure that the project already knows how to detect.

## Goal

When a developer or CI job runs `make ci`, repository configuration validation
must run as part of the same failure-aggregating pipeline.

## Users

- Contributors running local pre-merge checks.
- Maintainers reviewing PRs that touch configuration.
- CI workflows that rely on `make ci` as the comprehensive local equivalent.

## Non-Goals

- Do not rewrite `scripts/validate-configuration.sh`.
- Do not add new configuration validation rules.
- Do not change Docker startup behavior or test target behavior.

## Success Signal

- `make ci` output includes a configuration validation step.
- A failing `make validate-configuration` contributes a readable failure entry
  to the existing `failed_checks` summary.
- A Bats test pins this Makefile integration.

