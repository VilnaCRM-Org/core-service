# PRD: Issue #155 - Migration of Claude Skills from `user-service` to `core-service`

## Problem Statement

`core-service` and `user-service` already share a broadly similar Claude skill system, but the content has drifted in ways that matter for contributor behavior, AI-agent behavior, and repository correctness. `core-service` already contains BMALPH/autonomous-planning guidance, MongoDB-specific operational expectations, Codex wrappers, and onboarding references across multiple documentation surfaces. A parity-style copy from `user-service` would risk importing unsupported commands, wrong-stack examples, and overlapping skill guidance that conflicts with the current `core-service` baseline.

Issue `#155` needs a documentation and skill-system migration plan, not a runtime feature. The plan must define how to selectively consolidate useful `user-service` deltas into `core-service` while preserving repository-specific correctness and contributor clarity.

## Goals

- Use selective consolidation into the existing `core-service` skill system as the planning baseline.
- Preserve `core-service` as the source of truth for BMALPH guidance, autonomous-planning flow, MongoDB-specific correctness, and current onboarding expectations.
- Classify every meaningful migration delta as `adopt`, `adapt`, `defer`, or `exclude`.
- Produce an implementation-ready requirements set for a later documentation migration effort.
- Reduce contributor and AI-agent ambiguity around overlapping or conflicting skill guidance, especially for OpenAPI-related skills.
- Ensure documentation outside `.claude/skills` stays aligned whenever the migration changes contributor-facing instructions.

## Non-Goals

- No wholesale file copy from `user-service` into `core-service`.
- No runtime feature work, production code change, API behavior change, or infrastructure change.
- No lowering or reinterpreting `core-service` quality thresholds, CI expectations, or architecture rules.
- No attempt to standardize every VilnaCRM repository around a single global skill catalog in this issue.
- No migration of commands, validation gates, or examples that cannot be validated against actual `core-service` workflows.

## Personas and Stakeholders

### Primary Personas

- **Core-service maintainers**: own repository guidance quality, skill taxonomy clarity, and long-term maintainability.
- **Contributors and new joiners**: rely on onboarding and skill guidance to make correct changes without learning the repo by trial and error.
- **AI coding agents**: consume `AGENTS.md`, `.claude/skills`, `.agents/skills`, and `_bmad` guidance as operating instructions.
- **Reviewers**: need migration changes to be low-risk, traceable, and obviously correct for the target repo.

### Secondary Stakeholders

- **Technical leadership**: wants cross-repo consistency where it helps, without sacrificing repo-specific correctness.
- **User-service maintainers**: act as a source of potentially reusable guidance, examples, and policy improvements.

## Assumptions and Product Decisions

### Baseline Direction

- `core-service` is the canonical target for this migration effort.
- The migration is a reviewed consolidation exercise, not a parity-sync exercise.
- Existing `core-service` additions for BMALPH/autonomous planning and MongoDB-oriented guidance must remain authoritative unless the target repo itself has changed.

### Policy Portability

- Policy text from `user-service` is portable only when it maps cleanly to real `core-service` commands, review flows, and contributor expectations.
- Portable policy may land in repository-wide guidance files, individual skill files, or both, depending on scope.
- Policy that depends on unavailable commands or workflows must be rewritten for `core-service` or excluded.

### OpenAPI Skill Taxonomy

- The migration should not silently merge or duplicate `openapi-development` and `developing-openapi-specs`.
- Planning assumption: keep both skills in `core-service`, but clarify boundaries during the migration.
- Working boundary:
  - `developing-openapi-specs`: default entrypoint for endpoint documentation and OpenAPI-spec work.
  - `openapi-development`: advanced guidance for OpenAPI processor or layer-development patterns.
- If later review still finds overlap after clarification, consolidation can be tracked as a deferred follow-up rather than folded into this migration by default.

### Repo-Specific Examples

- Examples are not portable by default.
- `user-service` examples that mention repo-specific commands, bounded contexts, database assumptions, or file paths must be rewritten for `core-service` or excluded.
- No verbatim copy of repo-specific example content is allowed.

### Docs Outside `.claude/skills`

- The migration includes repository-facing documentation outside `.claude/skills` when those files teach or reference the skill system.
- At minimum, the later implementation must evaluate `AGENTS.md`, `.claude/skills/AI-AGENT-GUIDE.md`, `.claude/skills/SKILL-DECISION-GUIDE.md`, `.claude/skills/README.md`, `README.md`, `docs/getting-started.md`, and `docs/onboarding.md`.
- These files must either be updated or explicitly documented as unchanged with rationale in the migration work.

## Functional Requirements

### FR-1: Create a Meaningful Delta Inventory

The migration effort must produce a reviewable inventory of every meaningful delta between relevant `user-service` and `core-service` guidance assets in scope for issue `#155`.

For each delta, the inventory must record:

- source file or section
- target file or section
- delta type: policy, workflow, command reference, taxonomy, example, or cross-doc reference
- proposed disposition: `adopt`, `adapt`, `defer`, or `exclude`
- rationale for that disposition
- validation needed before implementation

Meaningful deltas include contributor-impacting differences in skill routing, command usage, review workflow, example correctness, OpenAPI guidance, and onboarding references. Low-signal wording-only churn should not be treated as a migration item unless it changes meaning.

### FR-2: Preserve the Canonical `core-service` Baseline

The migration effort must preserve `core-service` as the source of truth for:

- BMALPH and autonomous-planning guidance
- MongoDB-specific operational and performance correctness
- the current contributor onboarding path
- repository-supported commands and validation flows

The migration must not overwrite or dilute target-only guidance without explicit justification. The later implementation should treat `core-service` as the merge destination and reject any assumption that `user-service` is the default source of truth.

### FR-3: Apply a Structured Disposition Policy

Every meaningful delta must be assigned one of the following dispositions:

- `adopt`: bring the content into `core-service` with no material semantic change because it is already target-correct.
- `adapt`: bring the intent into `core-service`, but rewrite commands, stack assumptions, examples, file paths, or taxonomy references.
- `defer`: acknowledge the delta, but hold it for a later focused issue because it would expand scope or requires a broader design decision.
- `exclude`: explicitly reject the delta because it is invalid, redundant, or harmful in `core-service`.

The migration effort must not leave meaningful deltas unclassified.

### FR-4: Validate Policy Portability Before Migration

For policy-oriented content from `user-service`, the implementation must validate whether the policy is compatible with actual `core-service` behavior before adopting it.

This validation must check:

- command availability in `core-service`
- compatibility with existing BMALPH/autonomous-planning guidance
- compatibility with MongoDB and current architecture conventions
- compatibility with the current review and CI workflow

Policy placement rules:

- repository-wide operating rules belong in high-visibility guidance such as `AGENTS.md`, `.claude/skills/AI-AGENT-GUIDE.md`, `.claude/skills/SKILL-DECISION-GUIDE.md`, or `.claude/skills/README.md`
- workflow-specific rules belong in the relevant skill `SKILL.md`

Unsupported source references such as `make ai-review-loop` or other unavailable commands must be adapted or excluded. They must never be copied into `core-service` unchanged.

### FR-5: Resolve OpenAPI Skill Boundaries Explicitly

The migration effort must include an explicit taxonomy decision for `openapi-development` and `developing-openapi-specs`.

The implementation must:

- document the intended boundary between the two skills
- reflect that boundary consistently in discovery and onboarding surfaces
- ensure the default entrypoint is obvious to contributors and AI agents
- avoid introducing duplicate or contradictory routing guidance

The planning assumption for implementation is:

- keep both skills in place during the migration
- route general endpoint/OpenAPI-spec documentation work to `developing-openapi-specs`
- route advanced OpenAPI processor or layer-development work to `openapi-development`

Any stronger consolidation or removal decision should be treated as a separate, explicitly tracked follow-up if still needed after the clarification pass.

### FR-6: Handle Repo-Specific Examples by Adaptation or Exclusion

The migration effort must review each example candidate individually rather than copying entire example sets.

For every example considered, the implementation must verify:

- the example uses `core-service` paths and commands
- the example matches `core-service` bounded contexts and vocabulary
- the example is correct for Symfony, API Platform, GraphQL, MongoDB, and the repo’s DDD/CQRS conventions where relevant
- the example does not reference `user-service`-specific workflow assumptions

Examples that cannot be made correct without disproportionate churn should be excluded and noted in the delta inventory.

### FR-7: Keep External Guidance Surfaces in Sync

The migration effort must explicitly account for documentation outside `.claude/skills` that references the skill system or BMALPH usage.

At minimum, the implementation must evaluate and reconcile:

- `AGENTS.md`
- `.claude/skills/AI-AGENT-GUIDE.md`
- `.claude/skills/SKILL-DECISION-GUIDE.md`
- `.claude/skills/README.md`
- `README.md`
- `docs/getting-started.md`
- `docs/onboarding.md`

The migration deliverable must include a checklist showing which of these files changed, which remained unchanged, and why.

### FR-8: Produce a Reviewable Migration Package

The later documentation migration effort must produce a reviewable package that includes:

- the delta inventory and classification outcomes
- the affected file list
- a summary of adopted and adapted changes
- a summary of deferred and excluded changes with rationale
- a validation summary confirming that command references, taxonomy guidance, and repo-specific examples were checked

The package must make it easy for reviewers to confirm that the issue remains documentation- and guidance-only.

### FR-9: Preserve Scope Guardrails

The implementation must stay within documentation and skill-system migration scope.

Specifically, it must not:

- introduce runtime business functionality
- change application behavior
- alter BMALPH local assets in ways unrelated to documentation guidance
- smuggle in unrelated contributor-process redesign
- use directory-level wholesale copy as the migration mechanism

## Non-Functional Requirements

### NFR-1: Repository Correctness

All migrated content must be correct for `core-service` as it exists today, including supported commands, MongoDB usage, BMALPH integration, and contributor workflow.

### NFR-2: Clarity

The final guidance must reduce ambiguity for both humans and AI agents. Where multiple skills or docs could route the same task, the default path must be explicit.

### NFR-3: Traceability

Each meaningful migration decision must be traceable back to a specific source delta and disposition. Reviewers should be able to understand why a change was made or rejected.

### NFR-4: Low-Churn Discipline

The migration should minimize low-value wording churn. Edits should be intentionally scoped to correctness, clarity, and consistency improvements.

### NFR-5: Maintainability

The resulting documentation set should be easier to maintain in `core-service` than the current drifted state. Duplicate guidance should be minimized, and any deliberate overlap should be explicitly bounded.

### NFR-6: Cross-Document Consistency

Repository guidance, skill routing guidance, onboarding docs, and examples must not contradict each other after the migration.

## Acceptance Criteria

- A migration inventory exists for all meaningful in-scope deltas and every entry is classified as `adopt`, `adapt`, `defer`, or `exclude`.
- No planned migration item depends on a command or validation workflow that is not available in `core-service`.
- `core-service` remains the documented source of truth for BMALPH/autonomous-planning guidance and MongoDB-specific correctness.
- The relationship between `developing-openapi-specs` and `openapi-development` is explicitly documented and reflected consistently in skill-routing surfaces.
- No repo-specific example from `user-service` is copied verbatim into `core-service`.
- Each example included in the migration has been either adapted for `core-service` correctness or explicitly excluded.
- The later migration work includes a tracked checklist for the non-skill documentation surfaces in scope and records whether each file changed.
- The migration package clearly separates adopted/adapted content from deferred/excluded content.
- Reviewers can verify from the final migration package that the issue contains no runtime feature work and no wholesale copy strategy.

## Dependencies

- The existing `research.md` and `product-brief.md` artifacts for issue `#155`.
- The current `core-service` skill tree and repository guidance surfaces named in this PRD.
- Access to the compared `user-service` skill content used to derive the delta inventory.
- Maintainer review of any taxonomy clarifications that affect contributor entrypoints.
- A later implementation task or PR dedicated to documentation and skill-asset migration.

## Risks

- A parity mindset could override the selective-consolidation baseline and reintroduce wrong-stack or broken guidance.
- Policy text may appear portable but hide dependencies on commands or workflows not present in `core-service`.
- The OpenAPI skill overlap may remain confusing if the migration clarifies wording without clarifying entrypoints.
- Repo-specific examples may be subtly incorrect even after partial adaptation if they are not validated against the target repo.
- Docs outside `.claude/skills` may drift if the implementation updates only the skill tree.
- Because the two repositories are already close structurally, low-signal churn may consume review time without improving guidance quality.

## Rollout Considerations

- Treat the migration as a docs-only change set or a tightly scoped sequence of docs-only change sets.
- Recommended implementation order:
  1. finalize the delta inventory and dispositions
  2. update high-visibility routing and policy surfaces
  3. update affected skill files and examples
  4. reconcile external docs outside `.claude/skills`
  5. run the repository’s required validation for documentation changes
- Preserve a reviewer-friendly audit trail by summarizing what was adopted, adapted, deferred, and excluded in the PR description or migration summary.
- If the OpenAPI taxonomy clarification proves larger than expected, complete the rest of the migration and track taxonomy simplification separately rather than blocking the whole effort.

## Open Questions

- Should `core-service` become the canonical donor for future cross-repo skill alignment, or is this issue intentionally one-way migration only?
- After clarification, should `openapi-development` remain a visible contributor-facing skill, or should it later become a subordinate or advanced reference path?
- Do any `.agents/skills` wrappers need wording changes if `.claude/skills` routing language changes materially during implementation?
- Are there authoritative docs outside the files listed in this PRD, such as wiki pages or generated contributor docs, that also need a sync pass once the migration is implemented?
