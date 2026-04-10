# Migration Delta Inventory

This control artifact tracks the meaningful documentation and skill-surface deltas for issue `#155`.
Story `1.1` initializes the schema and governance notes; Story `1.2` populates and classifies the meaningful deltas before any broad file edits begin.

## Scope

- Tracked issue bundle: `specs/autonomous/core-service-plan-slack-1774829761.546879/`
- Canonical baseline: current `core-service` guidance remains authoritative unless the target repository disproves it.
- In scope: meaningful routing, policy, taxonomy, example, and portability deltas.
- Out of scope for Story `1.1`: contributor-facing doc edits, skill-file edits, wrapper edits, and runtime code changes.

## Inventory Schema

| Column | Meaning |
| --- | --- |
| Source | Source file, section, or reference being compared. |
| Target | Target `core-service` file or section affected by the delta. |
| Delta type | The kind of difference, such as routing, policy, taxonomy, example, command portability, or reference-only review. |
| Disposition | `adopt`, `adapt`, `defer`, or `exclude` once Story `1.2` classifies the delta. |
| Rationale | Why the disposition is correct for `core-service`. |
| Validation needed | Evidence needed before the delta can be considered done. |
| Implementation status | Current execution state such as `not started`, `in progress`, `implemented`, `validated`, `deferred`, or `excluded`. |

## Disposition Values

| Value | Meaning |
| --- | --- |
| `adopt` | The content is already correct for `core-service` and can be used without a material semantic rewrite. |
| `adapt` | The intent is useful, but commands, examples, paths, stack assumptions, or taxonomy wording must be rewritten. |
| `defer` | The item is acknowledged but moved to a later focused issue because it would broaden scope or needs a larger design decision. |
| `exclude` | The content is invalid, redundant, unsupported, or harmful for `core-service`. |

## Read-only References

These references may inform classification work, but they are not edit targets for this issue.

| Path | Role | Edit target |
| --- | --- | --- |
| `_bmad/COMMANDS.md` | Read-only BMALPH command reference for portability checks. | `no` |
| `_bmad/config.yaml` | Read-only BMALPH configuration reference for portability checks. | `no` |

## Delta Inventory

| Source | Target | Delta type | Disposition | Rationale | Validation needed | Implementation status |
| --- | --- | --- | --- | --- | --- | --- |
| `TBD in Story 1.2` | `TBD in Story 1.2` | `pending classification` | `TBD` | Story `1.1` initializes the schema only; meaningful deltas are added before contributor-facing docs or skill files are edited. | Confirm that each meaningful delta has a target, disposition, and validation note before implementation starts. | `not started` |
