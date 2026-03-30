# Architecture: Issue #155 - Migration of Claude Skills from `user-service` to `core-service`

## Architectural Framing

This architecture is for documentation governance and skill-system information architecture only. It does not change the runtime Symfony, API Platform, GraphQL, DDD, CQRS, or hexagonal architecture of `core-service`; those remain canonical and are referenced only to keep migrated guidance repository-correct.

Issue `#155` is a selective-consolidation migration. `core-service` remains the source of truth for BMALPH usage, autonomous-planning flow, MongoDB-specific correctness, and the current contributor onboarding path. The design hotspot is governance of agent instructions across multiple documentation surfaces, not application runtime design.

For this planning run, the issue bundle directory is `specs/autonomous/core-service-plan-slack-1774829761.546879/`.

## Project Context Analysis

The PRD defines a medium-complexity documentation migration with nine functional requirement groups and six non-functional requirement groups. The work is medium complexity because the repository already has a mature skill system and BMALPH integration, but the migration must reconcile drift across multiple canonical and mirror surfaces without introducing low-value churn.

Primary architectural drivers:

- preserve `core-service` as the canonical baseline
- classify every meaningful delta as `adopt`, `adapt`, `defer`, or `exclude`
- validate command portability before any content moves
- preserve the working OpenAPI boundary from the PRD
- keep repo-facing docs synchronized with skill-routing changes
- keep the scope documentation-only

Cross-cutting concerns:

- BMALPH and Codex wrapper correctness
- command validity in `core-service`
- OpenAPI taxonomy clarity
- example portability and bounded-context correctness
- cross-document consistency

## Current-State Architecture

`core-service` already operates a layered documentation system for contributors and AI agents:

- `_bmad/COMMANDS.md` and `_bmad/config.yaml` provide the canonical BMALPH command and configuration substrate.
- `AGENTS.md` defines the repository-wide operating contract and points agents into `.claude/skills`.
- `.claude/skills/AI-AGENT-GUIDE.md`, `.claude/skills/SKILL-DECISION-GUIDE.md`, and `.claude/skills/README.md` are the canonical routing and discovery surfaces for skill usage.
- `.claude/skills/**` is the operational skill catalog, with `SKILL.md`, references, and examples.
- `.agents/skills/**` is the Codex/BMALPH wrapper layer that maps command names to local BMALPH assets.
- `README.md`, `docs/getting-started.md`, and `docs/onboarding.md` mirror stable contributor guidance and BMALPH setup expectations.
- `docs/design-and-architecture.md` and `docs/developer-guide.md` are runtime reference docs that anchor OpenAPI-layer placement and DDD/CQRS rules.

Observed current-state hotspots:

- `core-service` is already the structural superset of `user-service`; migration is content reconciliation, not directory import.
- repository guide files have drifted materially from `user-service`
- some `user-service` policies reference unsupported commands such as `make ai-review-loop` and `make schemathesis-validate`
- `openapi-development` and `developing-openapi-specs` overlap in discovery surfaces and need explicit routing
- organization-example ownership is split between `code-organization` and `code-review`
- repo-facing docs already mention BMALPH and cannot be treated as optional mirrors

## Target-State Documentation Architecture

The target state is a governed documentation control plane with one planning/control layer and four contributor-facing layers.

```text
Issue bundle control artifacts
  -> canonical routing docs
      -> skill modules and examples
      -> Codex/BMALPH wrappers when needed
      -> repo-facing onboarding mirrors

Runtime architecture docs remain reference-only inputs, not migration targets by default.
```

Design intent for the target state:

- detailed migration reasoning stays in issue-scoped control artifacts, not in contributor onboarding docs
- canonical routing and policy live in `AGENTS.md` and the three root `.claude/skills` guide files
- operational details and adapted examples live in the owning skill directories under `.claude/skills`
- `.agents/skills` wrappers mirror canonical routing language only when the routing semantics or BMALPH handoff wording changes
- repo-facing docs mirror stable contributor entrypoints and setup steps, not the full migration rationale

The OpenAPI boundary from the PRD is preserved:

- `developing-openapi-specs` remains the default entrypoint for endpoint documentation and spec work
- `openapi-development` remains the advanced path for processor or layer-development guidance
- further consolidation is deferred unless later review proves the clarified boundary still fails

## Component and File Groups

| Group | Files | Role in migration |
| --- | --- | --- |
| Issue bundle control artifacts | `research.md`, `product-brief.md`, `prd.md`, `architecture.md`, future `migration-delta-inventory.md`, future `migration-sync-checklist.md`, `run-summary.md` | planning truth, delta inventory, dispositions, validation evidence, changed/unchanged checklist |
| Canonical routing docs | `AGENTS.md`, `.claude/skills/AI-AGENT-GUIDE.md`, `.claude/skills/SKILL-DECISION-GUIDE.md`, `.claude/skills/README.md` | authoritative task routing, policy portability decisions, taxonomy clarification |
| Skill modules | selected `.claude/skills/**` directories, especially `code-review`, `code-organization`, `openapi-development`, `developing-openapi-specs`, `documentation-creation` | actual adopted or adapted workflow text, references, and examples |
| Wrapper layer | `.agents/skills/bmad-autonomous-planning/SKILL.md`, `.agents/skills/bmad-bmalph/SKILL.md`, plus other wrappers only if routing text changes require it | Codex/BMALPH handoff alignment with canonical routing docs |
| Repo-facing mirrors | `README.md`, `docs/getting-started.md`, `docs/onboarding.md` | contributor setup and onboarding sync after canonical docs settle |
| Runtime reference docs | `docs/design-and-architecture.md`, `docs/developer-guide.md` | reference-only correctness anchors; touch only if terminology becomes inaccurate |

## Control Concern Ownership

The eventual implementation should place each migration concern in one explicit home:

- Delta inventory lives in `specs/autonomous/core-service-plan-slack-1774829761.546879/migration-delta-inventory.md`.
- Disposition logic lives in this architecture and is executed line-by-line in `migration-delta-inventory.md`.
- OpenAPI taxonomy clarification lives in `AGENTS.md`, `.claude/skills/AI-AGENT-GUIDE.md`, `.claude/skills/SKILL-DECISION-GUIDE.md`, `.claude/skills/README.md`, `.claude/skills/developing-openapi-specs/SKILL.md`, and `.claude/skills/openapi-development/SKILL.md`.
- Cross-doc sync lives in `specs/autonomous/core-service-plan-slack-1774829761.546879/migration-sync-checklist.md`, executed against `README.md`, `docs/getting-started.md`, `docs/onboarding.md`, and any changed `.agents/skills` wrappers.
- Skill-local adapted examples live in the owning skill directory under `.claude/skills`, not in the routing docs and not as verbatim copies from `user-service`.

## Migration Flow

1. Freeze the canonical baseline: treat current `core-service` BMALPH, autonomous-planning, MongoDB, and onboarding guidance as authoritative unless the target repo itself disproves it.
2. Build a meaningful delta inventory at file or section granularity from the compared `user-service` content.
3. Classify every meaningful delta as `adopt`, `adapt`, `defer`, or `exclude`, with rationale and required validation.
4. Update canonical routing docs first so contributor and AI-agent entrypoints are stable before deeper skill edits begin.
5. Reconcile the affected skill modules and examples under `.claude/skills/**`.
6. Update `.agents/skills/**` wrappers only if canonical routing or BMALPH handoff wording changed.
7. Sync repo-facing docs and produce a review package showing adopted, adapted, deferred, and excluded items plus the changed/unchanged checklist.

No step may skip directly to broad file editing before the delta inventory exists and is classified.

## Decision Rules and Disposition Policy

### Disposition policy

| Disposition | Meaning |
| --- | --- |
| `adopt` | content is already correct for `core-service` and can be brought in with no material semantic rewrite |
| `adapt` | intent is useful, but commands, examples, paths, stack assumptions, or taxonomy wording must be rewritten |
| `defer` | content is acknowledged but moved to a later focused issue because it would broaden scope or requires a larger design decision |
| `exclude` | content is invalid, redundant, unsupported, or harmful for `core-service` |

### Decision rules

- `core-service` wins any source-of-truth conflict involving BMALPH, autonomous planning, MongoDB, contributor setup, or current supported commands.
- Unsupported source commands are never copied verbatim. `make ai-review-loop`, `make schemathesis-validate`, and any other unavailable commands are `exclude` as direct text and only `adapt` if their intent can be expressed using real `core-service` workflows.
- Low-signal wording-only churn is `exclude` unless it changes meaning, routing, or policy.
- Examples are adapt-or-exclude only. No repo-specific example from `user-service` is copied verbatim.
- Generic examples belong to the owning skill. `code-review` may reference organization examples, but canonical organization examples should live under `code-organization`.
- The PRD OpenAPI boundary is preserved. `developing-openapi-specs` is the default discovery path; `openapi-development` is the advanced implementation path; catalog consolidation is deferred unless a later issue is opened.
- `.agents/skills` wrappers follow canonical routing docs; they do not define routing policy themselves.
- `_bmad/COMMANDS.md` and `_bmad/config.yaml` are read-only reference surfaces for this issue unless a separate scope decision explicitly expands the work.
- `docs/design-and-architecture.md` and `docs/developer-guide.md` remain correctness anchors and should stay unchanged unless the migration would otherwise make them inaccurate.

## Validation Architecture

| Validation gate | What it checks | Pass condition | Evidence |
| --- | --- | --- | --- |
| Inventory completeness | all meaningful deltas are captured | every meaningful delta has source, target, type, disposition, rationale, and validation need | `migration-delta-inventory.md` |
| Repository correctness | commands, stack assumptions, and BMALPH references are valid in `core-service` | no unsupported command or wrong-stack guidance remains in planned edits | inventory notes plus changed skill files |
| Routing consistency | the same task routes the same way across canonical docs | `AGENTS.md`, `AI-AGENT-GUIDE.md`, `SKILL-DECISION-GUIDE.md`, and `.claude/skills/README.md` agree | `migration-sync-checklist.md` |
| Skill correctness | examples, references, and workflow steps match `core-service` paths and conventions | no verbatim `user-service` example survives; OpenAPI boundary is explicit | updated skill directories |
| Cross-doc sync | contributor mirrors reflect the settled routing model | changed and unchanged files are recorded with rationale | `migration-sync-checklist.md` |
| Repository validation | the repo still passes required validation after doc edits | `make ci` passes with no scope creep into runtime code | CI output and review summary |

## Sequencing

1. Create `migration-delta-inventory.md` and `migration-sync-checklist.md` in the issue bundle.
2. Populate and classify the delta inventory before any broad contributor-facing doc edits.
3. Update canonical routing docs and lock the OpenAPI taxonomy wording.
4. Reconcile affected skill modules and examples under `.claude/skills/**`.
5. Update `.agents/skills/**` wrappers only where canonical routing changes require mirrored wording.
6. Reconcile `README.md`, `docs/getting-started.md`, and `docs/onboarding.md`.
7. Evaluate `docs/design-and-architecture.md` and `docs/developer-guide.md` explicitly and record either `unchanged` with rationale or the minimal needed edit.
8. Run validation, produce the adopted/adapted/deferred/excluded summary, and close with `make ci`.

## Risks and Mitigations

| Risk | Impact | Mitigation |
| --- | --- | --- |
| parity-copy mindset overrides selective consolidation | broken or diluted `core-service` guidance | freeze `core-service` as canonical baseline and require disposition per delta |
| unsupported commands leak into target docs | contributors and agents follow invalid workflows | command portability gate before any adoption or adaptation |
| OpenAPI overlap remains ambiguous | inconsistent skill routing and duplicated edits | settle canonical routing docs before editing deeper skill content |
| repo-specific examples are copied too literally | wrong bounded contexts, commands, or stack assumptions | example-by-example review with adapt-or-exclude rule |
| mirror docs drift from canonical docs | onboarding and setup become contradictory | dedicated cross-doc sync checklist and changed/unchanged record |
| low-value churn consumes review time | slower reviews and weaker trust in docs changes | exclude wording-only churn and keep the inventory focused on meaningful deltas |
| scope expands into `_bmad/` assets or runtime code | issue ceases to be documentation-only | read-only rule for BMALPH assets and explicit scope guardrails in review package |

## Architecture Readiness Assessment

This architecture is ready to drive later epics and stories for issue `#155`. It preserves the current `core-service` BMALPH/autonomous-planning architecture as canonical, keeps the working OpenAPI boundary intact, and defines explicit homes for delta inventory, disposition logic, taxonomy clarification, and cross-doc sync.

The first implementation task must be governance-first: create and complete the issue-bundle control artifacts before touching contributor-facing files. After that, the work can proceed as a controlled documentation migration without runtime change and without wholesale copy behavior.
