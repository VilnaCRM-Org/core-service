# Migration Sync Checklist

This control artifact records whether each in-scope documentation surface changes during issue `#155` and why.
Story `1.1` initializes the required evaluation targets. Final changed-or-unchanged outcomes are recorded as later stories complete.

## Status Legend

- `pending`: not evaluated yet
- `changed`: edited during this issue
- `unchanged`: evaluated and intentionally left as-is
- `read-only reference`: consulted only and never edited in this issue

## Required Execution Order

1. Canonical routing docs first: `AGENTS.md`, `.claude/skills/AI-AGENT-GUIDE.md`, `.claude/skills/SKILL-DECISION-GUIDE.md`, and `.claude/skills/README.md`.
2. Skill modules second: only the in-scope `.claude/skills/**` modules and examples governed by `migration-delta-inventory.md`.
3. Wrappers third if needed: `.agents/skills/**` mirrors only when canonical routing language or BMALPH handoff wording actually changed.
4. Repo-facing mirrors fourth: `README.md`, `docs/getting-started.md`, and `docs/onboarding.md` after canonical routing and skill wording settle.
5. Validation last: finalize changed-or-unchanged outcomes, explicitly evaluate the runtime reference docs, and run repository validation before closure.

## Scope Guardrails

- This issue stays documentation-only. No runtime code changes, infrastructure work, or behavior changes are in scope.
- The migration stays selective. No wholesale copy strategy from `user-service` is allowed; only classified deltas may be adopted or adapted.
- `_bmad/COMMANDS.md` and `_bmad/config.yaml` remain read-only references throughout this issue.
- `docs/design-and-architecture.md` and `docs/developer-guide.md` require an explicit changed-or-unchanged rationale before closure.

## Evaluation Targets

| Path or pattern                          | Group                  | Edit target     | Final status          | Rationale or notes                                                                                                                                                                                            |
| ---------------------------------------- | ---------------------- | --------------- | --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `AGENTS.md`                              | Canonical routing docs | `yes`           | `changed`             | Story `2.1`: clarified the docs-only scope for issue `#155`, preserved the `core-service` source-of-truth baseline, and restated the default routing for docs, review, and autonomous planning.               |
| `.claude/skills/AI-AGENT-GUIDE.md`       | Canonical routing docs | `yes`           | `changed`             | Story `2.1`: aligned the AI-agent guide with the `core-service` baseline, docs-only scope, and the canonical routing defaults for docs, review, and autonomous planning.                                      |
| `.claude/skills/SKILL-DECISION-GUIDE.md` | Canonical routing docs | `yes`           | `changed`             | Story `2.1`: aligned the routing decision surface with the `core-service` source-of-truth policy, docs-only scope, and the same default routes used by the other canonical docs.                              |
| `.claude/skills/README.md`               | Canonical routing docs | `yes`           | `changed`             | Story `2.1`: aligned the contributor-facing catalog mirror with the `core-service` baseline and the explicit default routing for docs, review, and autonomous planning.                                       |
| `.agents/skills/**`                      | Wrapper layer          | `conditional`   | `unchanged`           | No wrapper mirrors were needed for this issue because the changed routing language lives in canonical contributor docs and there are no parallel `.agents/skills` discovery surfaces for those updated paths. |
| `README.md`                              | Repo-facing mirrors    | `yes`           | `changed`             | Added a concise AI-agent routing summary so repository-level onboarding points back to the canonical docs without duplicating migration rationale.                                                            |
| `docs/getting-started.md`                | Repo-facing mirrors    | `yes`           | `changed`             | Added the settled BMALPH and OpenAPI routing summary for workspace users.                                                                                                                                     |
| `docs/onboarding.md`                     | Repo-facing mirrors    | `yes`           | `changed`             | Added the canonical AI-agent routing summary for new contributors and reviewers.                                                                                                                              |
| `docs/design-and-architecture.md`        | Runtime reference docs | `evaluate only` | `unchanged`           | Reviewed as a correctness anchor; its DDD and OpenAPI-layer placement guidance remained accurate after the documentation-only routing edits.                                                                  |
| `docs/developer-guide.md`                | Runtime reference docs | `evaluate only` | `unchanged`           | Reviewed as a correctness anchor; no terminology drift required a change after the settled routing and skill-surface updates.                                                                                 |
| `_bmad/COMMANDS.md`                      | Read-only references   | `no`            | `read-only reference` | Consult only for portability and routing comparisons; not an edit target in this issue.                                                                                                                       |
| `_bmad/config.yaml`                      | Read-only references   | `no`            | `read-only reference` | Consult only for portability and routing comparisons; not an edit target in this issue.                                                                                                                       |

## Story 1.2 Impact Notes

- Canonical routing rows are impacted by five classified delta groups: source-of-truth preservation for BMALPH/MongoDB/onboarding, unsupported-command portability, the deferred all-skills new-feature gate, locked-configuration governance portability, and OpenAPI taxonomy clarification.
- Skill-module deltas for `.claude/skills/code-review/**`, `.claude/skills/code-organization/**`, `.claude/skills/documentation-creation/**`, `.claude/skills/developing-openapi-specs/**`, `.claude/skills/openapi-development/**`, and `.claude/skills/query-performance-analysis/**` are governed by `migration-delta-inventory.md` even though they are not standalone checklist rows.
- Repo-facing mirror rows are also downstream consumers of the unsupported OpenAPI validation-command classifications, especially where `docs/onboarding.md` or similar mirrors currently imply source-only commands.
- The wrapper row remains conditional until the canonical-routing stories settle whether any `.agents/skills/**` wording actually changes.
- The repo-facing mirror rows stay downstream-only until canonical routing and skill-local wording are settled.
- The runtime reference-anchor rows stay evaluation-only; the current classification assumes `unchanged` unless later terminology review proves otherwise.

## Final Outcome Notes

- Canonical routing docs now encode the `core-service` source-of-truth baseline, the documentation-only scope of issue `#155`, and the explicit `developing-openapi-specs` versus `openapi-development` split.
- Skill-local edits were limited to the approved surfaces: `code-review`, `code-organization`, `developing-openapi-specs`, `openapi-development`, and the in-scope OpenAPI references/examples that previously pointed back to `user-service`.
- Wrapper mirrors stayed unchanged because there is no separate `.agents/skills` discovery surface that needed to repeat the updated contributor-facing routing language.
- Runtime reference docs were explicitly evaluated and kept unchanged with rationale, satisfying the architecture guardrail for `docs/design-and-architecture.md` and `docs/developer-guide.md`.
