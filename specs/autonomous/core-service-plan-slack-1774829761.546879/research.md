# Research: Issue #155 - Migration of Claude Skills from user-service to core-service

## Objective

Plan the migration of Claude skill assets and related guidance from `user-service` into `core-service` without proposing implementation code. This research is limited to repository-local evidence and is intended to feed later BMALPH planning artifacts.

## Evidence Reviewed

- core-service: `AGENTS.md`, `.claude/skills/AI-AGENT-GUIDE.md`, `.claude/skills/SKILL-DECISION-GUIDE.md`, `.claude/skills/README.md`, `docs/design-and-architecture.md`, `docs/developer-guide.md`, `docs/getting-started.md`, `docs/onboarding.md`, `README.md`
- user-service comparison clone: `AGENTS.md`, `.claude/skills/AI-AGENT-GUIDE.md`, `.claude/skills/SKILL-DECISION-GUIDE.md`, `.claude/skills/README.md`, and both `.claude/skills` inventories
- BMALPH context: `_bmad/COMMANDS.md`, `_bmad/bmm/agents/analyst.agent.yaml`, `_bmad/config.yaml`

## Current State

Core-service already treats the Claude skills system as a first-class operating model. The target repo instructs agents to start from `.claude/skills` guidance, already documents BMALPH usage in `AGENTS.md`, `README.md`, `docs/getting-started.md`, and `docs/onboarding.md`, and includes Codex wrappers under `.agents/skills/` plus local `_bmad/` assets. The repo-local BMAD config is active and resolves `planning_artifacts` to `_bmad-output/planning-artifacts`.

The target skill tree is already a superset in structure. Core-service has 109 files under `.claude/skills` across 20 top-level skill directories. User-service has 101 files across 18 top-level skill directories. There are no top-level skill directories present only in user-service.

## Source/Target Comparison

| Dimension                        | core-service                                           | user-service              | Planning implication                       |
| -------------------------------- | ------------------------------------------------------ | ------------------------- | ------------------------------------------ |
| Top-level skill dirs             | 20                                                     | 18                        | core is already the structural superset    |
| Top-level dirs unique to target  | `bmad-autonomous-planning`, `developing-openapi-specs` | none                      | preserve core-only additions               |
| Shared catalog size              | 18 dirs                                                | 18 dirs                   | migration is mostly content reconciliation |
| Stack-specific performance guide | MongoDB profiler                                       | MySQL slow query log      | do not copy across unchanged               |
| Repo-specific doc example        | `core-service-example.md`                              | `user-service-example.md` | examples must remain repo-specific         |

### High-signal shared-content deltas

- `AI-AGENT-GUIDE.md`, `SKILL-DECISION-GUIDE.md`, and `.claude/skills/README.md` are not in parity. Core-service adds BMALPH autonomous-planning guidance and points new API documentation work to `developing-openapi-specs`. User-service adds stronger policy text around a mandatory new-feature verification gate, locked configuration handling, and additional operational validation flows.
- `code-review/SKILL.md` differs materially. User-service assumes `make ai-review-loop`; core-service does not expose that command and instead centers the workflow on `make pr-comments`.
- `code-organization/` is richer in core-service overall, but user-service still has one review-style example file not present under the same path in core-service. Core-service has partly rehomed similar material under `code-review/examples/organization-fixes.md`.
- `openapi-development/` exists in both repos, but core-service also adds a separate `developing-openapi-specs/` skill. This creates overlap that later planning stages should resolve before any content backport.
- `query-performance-analysis/` diverges by database technology. Core-service ships MongoDB-oriented profiler guidance, while user-service ships MySQL-oriented slow-query guidance.
- `documentation-creation/` example files are repository-specific and should not be mirrored verbatim.

## Inferred Migration Scope Options

### Option 1: Minimal delta sync

Adopt only clearly missing, stack-neutral examples or policy text from user-service and leave the rest of core-service unchanged.

Pros:

- Lowest risk to existing BMALPH and MongoDB-specific guidance
- Smallest documentation churn
- Fastest to review

Cons:

- Leaves cross-repo wording and policy differences in place
- Does not resolve overlapping OpenAPI skill naming

### Option 2: Selective consolidation into the core-service baseline

Treat core-service as the canonical target, keep its BMALPH and target-stack guidance, and selectively merge useful user-service material into the corresponding core skills and guide files after command and stack validation.

Pros:

- Preserves target-specific strengths
- Can absorb valuable user-service policy improvements where they are valid
- Avoids blind duplication

Cons:

- Requires judgment file by file
- Needs an explicit taxonomy decision for OpenAPI-related skills

### Option 3: Full parity adaptation

Attempt to make core-service match all meaningful user-service skill content, adapting repo-specific commands and technology references as needed.

Pros:

- Maximum cross-repo standardization
- Fewer conceptual differences for contributors working across services

Cons:

- Highest effort and review burden
- Highest risk of importing invalid commands or wrong-stack guidance
- Likely duplicates or regresses existing core-service additions

## Constraints

- This issue is planning/specification only. No production implementation changes are in scope for this stage.
- BMALPH routing in core-service already exists and must remain intact. The target repo uses `_bmad/COMMANDS.md`, `.agents/skills/`, and local `_bmad/` configuration.
- Core-service documentation already embeds BMALPH references in multiple onboarding surfaces, so migration cannot assume a blank target.
- Core-service is MongoDB-based and user-service comparison content includes MySQL- and OAuth-oriented guidance that is not automatically portable.
- Some user-service policy text depends on commands not present in core-service, notably `make ai-review-loop` and `make schemathesis-validate`.
- The comparison source is a shallow clone at `/tmp/user-service`; committed skill artifacts were available, but git history and any ignored/generated local assets were not part of the comparison.

## Risks

- A naive copy strategy could overwrite or dilute core-service’s BMALPH planning workflow and autonomous-planning documentation.
- Importing user-service command references without validating target support would create broken guidance.
- Keeping both `openapi-development` and `developing-openapi-specs` without a clear boundary may increase agent confusion and inconsistent skill selection.
- Copying repo-specific examples verbatim would introduce wrong bounded contexts, database assumptions, or operational steps.
- Because inventories are already near-parity, low-signal churn is a real risk; later stages should justify each migrated file or section.

## Dependencies

- A later planning stage must decide whether the migration target is parity, selective consolidation, or a canonical core-service superset.
- OpenAPI skill taxonomy needs a single planning decision: keep two skills with clarified boundaries, or consolidate content into one primary entrypoint.
- Any policy backport from user-service must be checked against actual core-service commands and CI capabilities before it is included in the plan.
- If migration changes the skill catalog or agent workflow, secondary documentation updates will be needed in `AGENTS.md`, `.claude/skills/AI-AGENT-GUIDE.md`, `.claude/skills/SKILL-DECISION-GUIDE.md`, `.claude/skills/README.md`, `README.md`, `docs/getting-started.md`, and `docs/onboarding.md`.

## Recommended Planning Assumptions

- Use Option 2 as the planning baseline: selective consolidation into the existing core-service skill system.
- Treat core-service as the target-of-truth for BMALPH, MongoDB, and current onboarding flow.
- Do not plan a wholesale directory copy; plan a reviewed merge of specific content deltas.
- Exclude user-service content that depends on unavailable commands or wrong-stack assumptions unless it is first rewritten for core-service.
- Treat repo-specific example files as templates to adapt, not artifacts to duplicate verbatim.
- Assume that no top-level skill directory migration is required from user-service to core-service, because core-service already contains the full shared directory set plus target-only additions.
- Plan for follow-up normalization of OpenAPI skill boundaries, because that overlap is the most obvious catalog-level source of confusion in the target repo.

## Unresolved Questions

- Is the desired outcome strict cross-repo parity, or is core-service intended to become the canonical superset that other services later follow?
- Should user-service’s stricter policy text be migrated into core-service repository guidance, into individual skill files, or both?
- Should the user-service `code-organization` review example be reintroduced under that same skill in core-service, or is its partial relocation into `code-review/examples/` the intended structure?
- Should `openapi-development` remain as an advanced/internal skill while `developing-openapi-specs` stays the default entrypoint, or should the catalog be simplified?
- Does issue #155 include secondary doc updates outside `.claude/skills`, or is it limited to the skill tree and its immediate guide files?
