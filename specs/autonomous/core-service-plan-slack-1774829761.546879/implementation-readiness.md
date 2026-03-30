# Implementation Readiness: Issue #155 - Migration of Claude Skills from `user-service` to `core-service`

## Assessment Scope

This readiness assessment evaluates the planning bundle at `specs/autonomous/core-service-plan-slack-1774829761.546879/` for a future documentation-only implementation effort for issue `#155`.

Preserved assumptions for this stage:

- The tracked bundle directory `specs/autonomous/core-service-plan-slack-1774829761.546879/` is the authoritative deliverable location for this planning run, even though `_bmad/config.yaml` points `planning_artifacts` to `_bmad-output/planning-artifacts`.
- This stage evaluates readiness for a future docs-only implementation effort, not immediate execution.
- `_bmad/COMMANDS.md` and `_bmad/config.yaml` remain read-only reference surfaces for the future implementation described here.

## Readiness Summary

**Status:** Ready with execution guardrails.

The current planning bundle is sufficiently complete and internally aligned for a supervisor to turn it into a draft implementation plan or a later docs-only PR strategy. The bundle is not ready for ungoverned file editing yet; the future implementation must begin with the Epic 1 control artifacts `migration-delta-inventory.md` and `migration-sync-checklist.md` before contributor-facing docs or skill files are changed.

**Supervisor handoff verdict:** Yes. The bundle is ready for a supervisor to prepare a draft implementation plan later, provided the governance-first sequence in `epics.md` is preserved.

## Strengths

- The authoritative planning bundle is present and coherent.
  - Whole-document artifacts exist for `research.md`, `product-brief.md`, `prd.md`, `architecture.md`, `epics.md`, and `run-summary.md`.
  - No sharded duplicates or competing whole-versus-sharded variants were found in the tracked bundle.
- The baseline direction is now resolved.
  - Earlier ambiguity about parity copy versus selective consolidation is resolved across `product-brief.md`, `prd.md`, `architecture.md`, and `epics.md` in favor of selective consolidation with `core-service` as the canonical baseline.
- Scope discipline is consistent.
  - The planning artifacts repeatedly constrain the issue to documentation and skill-system migration only.
  - Runtime code changes, infrastructure work, wholesale copying, and edits to `_bmad/COMMANDS.md` or `_bmad/config.yaml` are consistently ruled out.
- Architecture and execution sequencing are aligned.
  - `architecture.md` defines a governance-first control plane.
  - `epics.md` operationalizes that design by requiring Epic 1 control artifacts before any contributor-facing edits begin.
- The highest-risk taxonomy question is bounded.
  - The OpenAPI split is no longer an unresolved planning hole.
  - The bundle consistently keeps `developing-openapi-specs` as the default entrypoint and `openapi-development` as the advanced path, while deferring broader consolidation unless later review proves it necessary.
- Validation expectations already exist.
  - The future implementation is required to produce adopted/adapted/deferred/excluded outcomes, changed-or-unchanged file rationale, and a final `make ci` result.

## Gaps

- The bundle does not yet include `migration-delta-inventory.md`.
  - This is expected at the planning stage, but it is the first required implementation artifact and must exist before broader doc edits begin.
- The bundle does not yet include `migration-sync-checklist.md`.
  - This is also expected at the planning stage, but it is required to control changed-or-unchanged decisions across canonical docs, wrappers, and repo-facing mirrors.
- The disposition model is defined, but the actual delta inventory is not yet populated.
  - A supervisor can draft an implementation plan from the current bundle, but implementation cannot claim portability decisions are complete until the inventory exists.
- Wrapper impact remains an evaluation item rather than a completed decision.
  - The architecture and epics define when `.agents/skills/**` should be updated, but later implementation still needs to determine which wrappers, if any, require wording changes.
- `docs/design-and-architecture.md` and `docs/developer-guide.md` are treated correctly as reference anchors, but not yet recorded as final changed-or-unchanged outcomes.
  - This is acceptable at readiness stage and should be closed by the future sync checklist.

## Blockers

No hard planning blockers remain for supervisor handoff.

Execution gates that still matter before implementation begins:

- `migration-delta-inventory.md` must be created and populated in the tracked bundle before contributor-facing doc edits begin.
- `migration-sync-checklist.md` must be created and used as the cross-document control plane before contributor-facing doc edits begin.
- Unsupported source commands such as `make ai-review-loop` and `make schemathesis-validate` must remain `adapt` or `exclude` items and must never be copied into `core-service` unchanged.

## Dependency Checks

- Planning bundle inputs: present.
  - Required planning inputs were available for this assessment: `research.md`, `product-brief.md`, `prd.md`, `architecture.md`, and `epics.md`.
- Repository guidance inputs: present.
  - `AGENTS.md`, `.claude/skills/AI-AGENT-GUIDE.md`, `.claude/skills/SKILL-DECISION-GUIDE.md`, `.claude/skills/README.md`, `README.md`, `docs/getting-started.md`, `docs/onboarding.md`, `docs/design-and-architecture.md`, and `docs/developer-guide.md` were available and align with the planning direction.
- BMALPH command context: present.
  - `_bmad/COMMANDS.md` exposes `implementation-readiness`.
  - `_bmad/bmm/agents/architect.agent.yaml` provides the architect role backing for the command.
  - The readiness workflow and step-01 discovery files exist and support the document-inventory-first approach.
- Core repository command support: verified.
  - The `Makefile` contains `make ci`, `make pr-comments`, `make generate-openapi-spec`, `make validate-openapi-spec`, and `make validate-configuration`.
- Unsupported source command references: correctly treated as non-portable.
  - `make ai-review-loop` is not exposed by the core-service `Makefile`.
  - `make schemathesis-validate` is not exposed by the core-service `Makefile`.
  - The planning bundle already treats these as adapt-or-exclude cases rather than direct adoption candidates.
- Skill-path dependencies: verified.
  - `.claude/skills/developing-openapi-specs/` exists.
  - `.claude/skills/openapi-development/` exists.
  - `.claude/skills/code-review/` exists.
  - `.claude/skills/code-organization/` exists.
  - `.agents/skills/bmad-implementation-readiness/` exists.

## Artifact Alignment Checks

- Research to Product Brief: aligned.
  - Both establish selective consolidation into the existing `core-service` skill system as the planning baseline.
- Product Brief to PRD: aligned.
  - Both preserve `core-service` as the source of truth for BMALPH guidance, MongoDB-specific correctness, supported commands, and contributor onboarding.
  - Both define the issue as docs-only rather than a runtime feature.
- PRD to Architecture: aligned.
  - The PRD disposition model (`adopt`, `adapt`, `defer`, `exclude`) is preserved in the architecture.
  - The PRD OpenAPI boundary is preserved in the architecture.
  - Read-only treatment of `_bmad/COMMANDS.md` and `_bmad/config.yaml` is preserved.
- Architecture to Epics: aligned.
  - Epic 1 operationalizes the architecture requirement to create the control artifacts first.
  - Later epics follow the architecture’s ordering: canonical routing docs, skill modules and examples, wrappers, mirrors, then validation.
- Planning bundle to repository docs: aligned.
  - `AGENTS.md` already distinguishes `developing-openapi-specs` from `openapi-development`.
  - Repository guidance already supports BMALPH and autonomous-planning concepts that the bundle preserves rather than replacing.
  - The docs set already treats `.claude/skills` as a first-class operating surface, which matches the migration scope.
- Document discovery exception handling: aligned.
  - No UX design artifact exists in the tracked bundle.
  - This is not a blocker because `epics.md` explicitly states that no UX design specification is required for this documentation-governance issue.

## Earlier Concerns Resolved

- Earlier scope ambiguity: resolved.
  - The current full input set now clearly selects selective consolidation into `core-service` as the canonical baseline rather than parity copy or open-ended backporting.
- Earlier naive-copy risk: resolved as a planning decision.
  - The current full input set now explicitly rejects wholesale copying and requires reviewed disposition of each meaningful delta.
  - This remains a warning for future execution, but it is no longer an unresolved planning concern.
- Earlier OpenAPI split concern: resolved enough for readiness.
  - The current full input set now defines a stable default-versus-advanced boundary between `developing-openapi-specs` and `openapi-development`.
  - Possible future simplification remains a follow-up decision, not a blocker to drafting implementation work.

## Validation Expectations

This readiness stage does not require immediate implementation execution. For the future docs-only implementation effort, the supervisor or implementer should treat the following as required validation gates:

1. Create and populate `migration-delta-inventory.md` with source, target, delta type, disposition, rationale, validation needed, and implementation status.
2. Create and maintain `migration-sync-checklist.md` with changed-or-unchanged rationale for all in-scope canonical docs, wrapper files, repo-facing mirrors, and the two reference-anchor docs.
3. Validate every adopted or adapted policy reference against actual `core-service` commands and workflows before editing the target files.
4. Preserve the OpenAPI routing boundary consistently across canonical routing docs and skill-local docs.
5. Adapt or exclude repo-specific examples; copy none verbatim from `user-service`.
6. Keep `_bmad/COMMANDS.md` and `_bmad/config.yaml` unchanged throughout the implementation for this issue.
7. Capture a final migration package that summarizes adopted, adapted, deferred, and excluded outcomes and links them back to the delta inventory.
8. Run `make ci` before closure of the future implementation effort and record the outcome in the implementation review package or `run-summary.md`.

## Recommendation

Proceed to draft implementation planning.

The current planning bundle is ready for a supervisor to convert into a controlled implementation plan or a later docs-only PR strategy. Readiness should be treated as approved with guardrails, not as permission to start editing arbitrarily.

Recommended first move for the future implementation:

1. Execute Story 1.1 from `epics.md` to create `migration-delta-inventory.md` and `migration-sync-checklist.md` in the tracked bundle.
2. Execute Story 1.2 to classify every meaningful delta before any high-visibility guidance files are edited.
3. Only then proceed to canonical routing updates, skill reconciliation, wrapper evaluation, repo-facing mirror updates, and final validation.

In short: the bundle is ready for supervisor planning and later controlled execution, but not for direct migration edits that skip the governance artifacts.
