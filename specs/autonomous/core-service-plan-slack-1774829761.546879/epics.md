# core-service - Epic Breakdown

## Overview

This document provides the draft epic and story breakdown for issue `#155`, covering the documentation-only migration of Claude skill guidance from `user-service` into `core-service`.

This plan assumes `core-service` remains the canonical baseline for BMALPH guidance, autonomous planning, MongoDB-specific correctness, and contributor onboarding. Stories are intentionally ordered so governance and control artifacts are created first, canonical routing docs are updated second, skill-local reconciliation happens third, and mirror docs plus validation close the issue last.

Implementation rule: no contributor-facing documentation or skill file should be edited before Epic 1 is complete.

## Requirements Inventory

### Functional Requirements

FR-1: Create a reviewable inventory of every meaningful migration delta between in-scope `user-service` and `core-service` guidance assets.

FR-2: Preserve `core-service` as the source of truth for BMALPH guidance, autonomous-planning flow, MongoDB-specific correctness, and the current contributor onboarding path.

FR-3: Classify every meaningful delta as `adopt`, `adapt`, `defer`, or `exclude`, with no unclassified in-scope deltas left behind.

FR-4: Validate policy portability before migration so unsupported commands, workflows, or stack assumptions are adapted or excluded rather than copied verbatim.

FR-5: Resolve the `developing-openapi-specs` versus `openapi-development` boundary explicitly while keeping both skills in place.

FR-6: Review repo-specific examples individually and handle each by adaptation or exclusion rather than wholesale copying.

FR-7: Keep external documentation surfaces in sync, at minimum `AGENTS.md`, `.claude/skills/AI-AGENT-GUIDE.md`, `.claude/skills/SKILL-DECISION-GUIDE.md`, `.claude/skills/README.md`, `README.md`, `docs/getting-started.md`, and `docs/onboarding.md`.

FR-8: Produce a reviewable migration package that summarizes delta classifications, affected files, adopted and adapted changes, deferred and excluded items, and validation evidence.

FR-9: Preserve documentation-only scope guardrails and avoid runtime code changes, infrastructure changes, wholesale directory copies, or unrelated process redesign.

### NonFunctional Requirements

NFR-1: All migrated guidance must be correct for the current `core-service` repository, including supported commands, MongoDB assumptions, BMALPH integration, and contributor workflows.

NFR-2: The final documentation set must reduce ambiguity for maintainers, contributors, reviewers, and AI agents.

NFR-3: Migration decisions must remain traceable from source delta to disposition and final implementation outcome.

NFR-4: The migration should minimize low-value wording churn and focus on correctness, clarity, and consistency.

NFR-5: The resulting documentation set must be easier to maintain than the current drifted state, with deliberate overlap only where it is explicitly bounded.

NFR-6: Canonical routing docs, skill files, wrappers, onboarding docs, and retained examples must not contradict each other after implementation.

### Additional Requirements

- Treat `specs/autonomous/core-service-plan-slack-1774829761.546879/` as the tracked issue bundle for this migration plan.
- The first implementation work must create `migration-delta-inventory.md` and `migration-sync-checklist.md` inside that tracked bundle.
- Update canonical routing and policy surfaces before editing deeper skill files or onboarding mirrors.
- Preserve the PRD OpenAPI decision: keep both `developing-openapi-specs` and `openapi-development`, but clarify their boundaries.
- Treat `_bmad/COMMANDS.md` and `_bmad/config.yaml` as read-only reference surfaces for this issue.
- Unsupported source commands such as `make ai-review-loop` and `make schemathesis-validate` must never be copied into `core-service` unchanged.
- Repo-specific examples must use `core-service` paths, commands, bounded-context terminology, and stack assumptions, or they must be excluded.
- `.agents/skills/**` wrappers should be updated only if canonical routing language changes require mirrored wording.
- `README.md`, `docs/getting-started.md`, and `docs/onboarding.md` must be reconciled after canonical routing settles.
- `docs/design-and-architecture.md` and `docs/developer-guide.md` should be explicitly evaluated and recorded as changed or unchanged with rationale.
- Close the implementation with a reviewer-friendly package and repository validation, including `make ci`.

### UX Design Requirements

None. No UX design specification was provided for this issue, and the work remains documentation governance and skill-catalog migration only.

### FR Coverage Map

| Requirement | Covered By              |
| ----------- | ----------------------- |
| FR-1        | 1.1, 1.2                |
| FR-2        | 1.3, 2.1, 4.2           |
| FR-3        | 1.2                     |
| FR-4        | 1.2, 2.1, 3.1, 3.4      |
| FR-5        | 2.2, 3.3                |
| FR-6        | 1.2, 3.2, 3.4           |
| FR-7        | 1.1, 2.1, 4.1, 4.2      |
| FR-8        | 4.3                     |
| FR-9        | 1.3, 4.3                |
| NFR-1       | 1.2, 2.1, 3.1, 3.3, 4.2 |
| NFR-2       | 2.1, 2.2, 3.3           |
| NFR-3       | 1.1, 1.2, 4.3           |
| NFR-4       | 1.2, 3.4                |
| NFR-5       | 2.1, 3.2, 3.3           |
| NFR-6       | 2.1, 3.3, 4.1, 4.2      |

## Epic List

| Epic | Title                                    | Goal                                                                                                                                     | Depends On        |
| ---- | ---------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- | ----------------- |
| 1    | Governance and Control Baseline          | Establish the tracked control artifacts, delta classification model, and scope guardrails before any contributor-facing doc edits begin. | None              |
| 2    | Canonical Routing and Policy Surfaces    | Lock the repository’s high-visibility routing and policy language to the `core-service` baseline.                                        | Epic 1            |
| 3    | Skill Catalog and Example Reconciliation | Apply the settled policy to in-scope skills and examples, including OpenAPI boundary clarification and review workflow adaptation.       | Epic 2            |
| 4    | Wrapper, Mirror Docs, and Review Package | Mirror the final routing model across wrappers and onboarding docs, then close with validation and a reviewable package.                 | Epic 2 and Epic 3 |

## Epic 1: Governance and Control Baseline

Establish the tracked issue-bundle artifacts and freeze the migration rules before any broader documentation edits begin.

### Story 1.1: Create the tracked migration control artifacts

As a core-service maintainer,  
I want the issue bundle to contain the migration control artifacts from the start,  
So that every later documentation change is traceable and governed.

**Objective:** Create `migration-delta-inventory.md` and `migration-sync-checklist.md` inside `specs/autonomous/core-service-plan-slack-1774829761.546879/` with the agreed schema, scope, and target file list.

**Acceptance Criteria:**

- Given the tracked issue bundle path, when Story 1.1 is completed, then `migration-delta-inventory.md` exists and includes columns or sections for source, target, delta type, disposition, rationale, validation needed, and implementation status.
- Given the tracked issue bundle path, when Story 1.1 is completed, then `migration-sync-checklist.md` exists and includes rows for `AGENTS.md`, `.claude/skills/AI-AGENT-GUIDE.md`, `.claude/skills/SKILL-DECISION-GUIDE.md`, `.claude/skills/README.md`, `README.md`, `docs/getting-started.md`, and `docs/onboarding.md`.
- Given the architecture guidance, when Story 1.1 is completed, then the checklist also includes `.agents/skills/**` wrapper evaluation and explicit changed-or-unchanged placeholders for `docs/design-and-architecture.md` and `docs/developer-guide.md`.
- Given `_bmad/COMMANDS.md` and `_bmad/config.yaml`, when the artifacts are initialized, then both files are listed as read-only references and not as edit targets.

**Dependencies:** None.

**Changes:**

- Create `specs/autonomous/core-service-plan-slack-1774829761.546879/migration-delta-inventory.md`.
- Create `specs/autonomous/core-service-plan-slack-1774829761.546879/migration-sync-checklist.md`.
- Do not edit contributor-facing docs or skill files in this story.

### Story 1.2: Classify every meaningful migration delta

As a core-service maintainer,  
I want every meaningful source-target delta classified before file edits begin,  
So that the migration stays selective, reviewable, and repository-correct.

**Objective:** Populate the delta inventory at file or section granularity and assign `adopt`, `adapt`, `defer`, or `exclude` to every meaningful delta.

**Acceptance Criteria:**

- Given the in-scope comparison set, when Story 1.2 is completed, then every meaningful delta has an entry with source, target, delta type, disposition, rationale, and validation need.
- Given the portability rules, when Story 1.2 is completed, then unsupported commands such as `make ai-review-loop` and `make schemathesis-validate` are never marked `adopt` and are instead recorded as `adapt` or `exclude`.
- Given stack-specific and repo-specific content, when Story 1.2 is completed, then MySQL-specific, OAuth-specific, or wrong-context guidance is marked for adaptation, exclusion, or deferral with explicit rationale.
- Given the low-churn requirement, when Story 1.2 is completed, then wording-only differences that do not change routing, policy, or correctness are explicitly excluded from scope.

**Dependencies:** 1.1.

**Changes:**

- Populate `migration-delta-inventory.md`.
- Add impacted-file notes to `migration-sync-checklist.md` where needed.
- Do not update canonical docs or skill files yet.

### Story 1.3: Freeze scope guardrails and execution order

As a reviewer,  
I want the migration sequence and scope guardrails locked before implementation spreads,  
So that the issue remains documentation-only and does not drift into broad refactoring.

**Objective:** Convert the classified inventory into an execution order that prioritizes governance, then canonical routing, then skill modules, then mirrors, then validation.

**Acceptance Criteria:**

- Given the classified inventory, when Story 1.3 is completed, then `migration-sync-checklist.md` records the required implementation order: canonical routing docs first, skill modules second, wrappers third if needed, repo-facing mirrors fourth, and validation last.
- Given the architecture constraints, when Story 1.3 is completed, then the control artifacts record explicit guardrails: no runtime code changes, no wholesale copy strategy, no `_bmad/COMMANDS.md` edits, and no `_bmad/config.yaml` edits.
- Given the architecture’s reference-only documents, when Story 1.3 is completed, then `docs/design-and-architecture.md` and `docs/developer-guide.md` are listed as evaluation items that require an explicit changed-or-unchanged rationale before closure.

**Dependencies:** 1.1, 1.2.

**Changes:**

- Update `migration-sync-checklist.md` with execution order and scope guardrails.
- Update `migration-delta-inventory.md` with any deferred or excluded scope notes.
- Keep contributor-facing docs unchanged in this story.

## Epic 2: Canonical Routing and Policy Surfaces

Update the highest-visibility repository guidance first so maintainers, contributors, and AI agents all route the migration the same way.

### Story 2.1: Align high-visibility guidance to the core-service baseline

As a contributor,  
I want the repository’s top-level guidance to reflect the actual `core-service` workflow,  
So that I am routed correctly before I reach deeper skill files.

**Objective:** Update `AGENTS.md`, `.claude/skills/AI-AGENT-GUIDE.md`, `.claude/skills/SKILL-DECISION-GUIDE.md`, and `.claude/skills/README.md` so they consistently encode the selective-consolidation policy and the `core-service` source-of-truth rules.

**Acceptance Criteria:**

- Given the migration baseline, when Story 2.1 is completed, then all four canonical routing docs state that `core-service` is authoritative for BMALPH usage, autonomous planning, MongoDB-specific correctness, onboarding flow, and supported commands.
- Given the portability rules, when Story 2.1 is completed, then portable user-service policy text is adapted to existing `core-service` commands and unsupported references are removed or excluded.
- Given the docs-only scope, when Story 2.1 is completed, then the updated guidance makes clear that issue `#155` is a documentation migration and not a runtime feature effort.
- Given cross-document consistency requirements, when Story 2.1 is completed, then the four files no longer disagree on default routing for documentation, review, and BMALPH planning tasks.

**Dependencies:** 1.2, 1.3.

**Changes:**

- Update `AGENTS.md`.
- Update `.claude/skills/AI-AGENT-GUIDE.md`.
- Update `.claude/skills/SKILL-DECISION-GUIDE.md`.
- Update `.claude/skills/README.md`.

### Story 2.2: Clarify the OpenAPI taxonomy in canonical routing docs

As an AI agent or contributor,  
I want the OpenAPI skill boundary to be explicit in discovery surfaces,  
So that I know which skill is the default and which is the advanced path.

**Objective:** Preserve both OpenAPI skills while clarifying the default and advanced entrypoints across the canonical routing docs.

**Acceptance Criteria:**

- Given the PRD decision, when Story 2.2 is completed, then all canonical routing docs state that `developing-openapi-specs` is the default entrypoint for endpoint documentation and spec work.
- Given the PRD decision, when Story 2.2 is completed, then all canonical routing docs state that `openapi-development` is the advanced path for processor or layer-development guidance.
- Given the scope guardrails, when Story 2.2 is completed, then no document implies the two skills are silently merged or duplicate defaults.
- Given the future taxonomy question, when Story 2.2 is completed, then any broader consolidation remains tracked as a deferred follow-up rather than being absorbed into this issue.

**Dependencies:** 2.1.

**Changes:**

- Refine OpenAPI routing language in `AGENTS.md`.
- Refine OpenAPI routing language in `.claude/skills/AI-AGENT-GUIDE.md`.
- Refine OpenAPI routing language in `.claude/skills/SKILL-DECISION-GUIDE.md`.
- Refine OpenAPI routing language in `.claude/skills/README.md`.
- Record the settled boundary in `migration-delta-inventory.md` and `migration-sync-checklist.md`.

## Epic 3: Skill Catalog and Example Reconciliation

Apply the settled routing and portability rules to the in-scope skill modules and examples without importing wrong-stack or unsupported content.

### Story 3.1: Reconcile the code-review workflow with target-supported commands

As a maintainer handling review feedback,  
I want the review skill to reflect real `core-service` commands,  
So that reviewers and AI agents do not follow unsupported workflows.

**Objective:** Adapt any useful review-policy text into `.claude/skills/code-review/SKILL.md` while preserving `make pr-comments` and the existing `core-service` review flow.

**Acceptance Criteria:**

- Given the current target workflow, when Story 3.1 is completed, then `.claude/skills/code-review/SKILL.md` contains no `make ai-review-loop` reference.
- Given the policy-portability rule, when Story 3.1 is completed, then any stricter review or verification guidance adopted from `user-service` is rewritten to use actual `core-service` review and CI flows.
- Given cross-skill consistency requirements, when Story 3.1 is completed, then the review skill still routes architecture, organization, and quality concerns to the correct companion skills.

**Dependencies:** 2.1.

**Changes:**

- Update `.claude/skills/code-review/SKILL.md`.
- Update any related review examples only if the delta inventory approved them.
- Mark final review-workflow dispositions in `migration-delta-inventory.md`.

### Story 3.2: Resolve code-organization example ownership and portability rules

As a reviewer or contributor,  
I want organization examples to live in the right skill home,  
So that documentation is easier to maintain and does not duplicate itself.

**Objective:** Settle whether organization-focused examples belong under `code-organization`, `code-review`, or both via cross-reference, and adapt or exclude any candidate example accordingly.

**Acceptance Criteria:**

- Given the current split ownership, when Story 3.2 is completed, then `code-organization` and `code-review` no longer imply competing ownership of the same example content.
- Given the example-portability rule, when Story 3.2 is completed, then any user-service example candidate is either adapted into the owning `core-service` skill or excluded with rationale.
- Given the maintainability requirement, when Story 3.2 is completed, then example guidance clearly states that repo-specific examples must use `core-service` paths, commands, naming, and bounded-context terminology.

**Dependencies:** 1.2, 2.1, 3.1.

**Changes:**

- Update `.claude/skills/code-organization/SKILL.md` if routing or ownership wording changes.
- Update any in-scope example files under `.claude/skills/code-organization/` or `.claude/skills/code-review/`.
- Record unchanged or excluded example candidates in `migration-delta-inventory.md`.

### Story 3.3: Clarify the skill-local OpenAPI boundary

As a contributor working on OpenAPI-related docs,  
I want the two OpenAPI skills to describe different scopes,  
So that I can choose the right path without guesswork.

**Objective:** Update `.claude/skills/developing-openapi-specs/SKILL.md` and `.claude/skills/openapi-development/SKILL.md` so they reflect the settled canonical boundary.

**Acceptance Criteria:**

- Given the PRD boundary, when Story 3.3 is completed, then `developing-openapi-specs` describes itself as the default entrypoint for endpoint documentation and OpenAPI-spec work.
- Given the PRD boundary, when Story 3.3 is completed, then `openapi-development` describes itself as advanced guidance for processor or layer-development patterns.
- Given the clarity requirement, when Story 3.3 is completed, then each skill links to the other as a deliberate adjacent path rather than as a competing default.
- Given the architecture constraints, when Story 3.3 is completed, then the story does not modify `_bmad/COMMANDS.md` or `_bmad/config.yaml`.

**Dependencies:** 2.2.

**Changes:**

- Update `.claude/skills/developing-openapi-specs/SKILL.md`.
- Update `.claude/skills/openapi-development/SKILL.md`.
- Update any directly adjacent README or reference text inside those skill directories if the settled wording requires it.

### Story 3.4: Reconcile remaining repository-specific examples and exclusions

As a reviewer,  
I want the remaining migrated examples and references to be explicitly justified,  
So that the issue does not introduce low-signal churn or wrong-stack guidance.

**Objective:** Apply the adapt-or-exclude rule to the remaining in-scope example and reference candidates, especially repository-specific documentation examples and stack-specific operational guidance.

**Acceptance Criteria:**

- Given the delta inventory, when Story 3.4 is completed, then no repo-specific user-service example is copied verbatim into `core-service`.
- Given the repository baseline, when Story 3.4 is completed, then MongoDB-specific, BMALPH-specific, and existing `core-service` onboarding guidance remain authoritative wherever equivalent source deltas were reviewed.
- Given the portability constraints, when Story 3.4 is completed, then non-portable MySQL, OAuth, or unsupported-command content is excluded or deferred with clear rationale.
- Given the low-churn requirement, when Story 3.4 is completed, then only inventory-approved example and reference edits are made.

**Dependencies:** 1.2, 2.1, 3.2, 3.3.

**Changes:**

- Update selected `.claude/skills/**` example or reference files only where the inventory marked them `adopt` or `adapt`.
- Record all excluded and deferred example candidates in `migration-delta-inventory.md`.
- Leave unrelated skill directories unchanged.

## Epic 4: Wrapper, Mirror Docs, and Review Package

Mirror the settled routing model into wrapper and onboarding surfaces, then close the migration with a reviewer-friendly validation package.

### Story 4.1: Align `.agents/skills` wrappers only where routing changed

As a Codex or BMALPH user,  
I want wrapper skills to mirror the canonical routing language without inventing new policy,  
So that wrappers remain aligned with the repository’s guidance surfaces.

**Objective:** Update only the affected `.agents/skills/**/SKILL.md` wrapper files when canonical routing or handoff wording changed during Epics 2 and 3.

**Acceptance Criteria:**

- Given the wrapper-layer rule, when Story 4.1 is completed, then wrapper updates are limited to cases where canonical routing language or BMALPH handoff wording actually changed.
- Given the OpenAPI and policy clarifications, when Story 4.1 is completed, then changed wrappers mirror the settled contributor-facing routing and do not redefine policy themselves.
- Given the read-only BMAD surfaces, when Story 4.1 is completed, then `_bmad/COMMANDS.md` and `_bmad/config.yaml` remain unchanged.
- Given the sync requirement, when Story 4.1 is completed, then unchanged wrapper files are recorded as unchanged with rationale in `migration-sync-checklist.md`.

**Dependencies:** 2.1, 2.2, 3.3.

**Changes:**

- Update only the necessary `.agents/skills/**/SKILL.md` files.
- Update `migration-sync-checklist.md` with changed-or-unchanged wrapper outcomes.

### Story 4.2: Sync repo-facing contributor docs and record unchanged runtime references

As a new contributor,  
I want the repo-facing docs to match the settled skill-routing model,  
So that onboarding remains consistent with the canonical guidance.

**Objective:** Reconcile `README.md`, `docs/getting-started.md`, and `docs/onboarding.md` to the final routing language, and explicitly record whether `docs/design-and-architecture.md` and `docs/developer-guide.md` stay unchanged.

**Acceptance Criteria:**

- Given the settled canonical guidance, when Story 4.2 is completed, then `README.md`, `docs/getting-started.md`, and `docs/onboarding.md` reflect the final BMALPH and skill-routing language without duplicating migration rationale.
- Given the cross-document consistency requirement, when Story 4.2 is completed, then those three docs do not contradict `AGENTS.md`, `.claude/skills/AI-AGENT-GUIDE.md`, `.claude/skills/SKILL-DECISION-GUIDE.md`, or `.claude/skills/README.md`.
- Given the architecture’s evaluation requirement, when Story 4.2 is completed, then `migration-sync-checklist.md` records changed-or-unchanged status and rationale for `README.md`, `docs/getting-started.md`, `docs/onboarding.md`, `docs/design-and-architecture.md`, and `docs/developer-guide.md`.
- Given the portability constraints, when Story 4.2 is completed, then no repo-facing doc introduces unsupported commands or wrong-stack assumptions.

**Dependencies:** 2.1, 2.2, 3.1, 3.3.

**Changes:**

- Update `README.md`.
- Update `docs/getting-started.md`.
- Update `docs/onboarding.md`.
- Update `migration-sync-checklist.md`.
- Update `docs/design-and-architecture.md` or `docs/developer-guide.md` only if terminology would otherwise be inaccurate.

### Story 4.3: Produce the reviewer package and validate docs-only closure

As a reviewer,  
I want a complete migration summary with validation evidence,  
So that I can confirm the issue stayed in scope and the documentation set is coherent.

**Objective:** Finalize the migration package, record adopted/adapted/deferred/excluded outcomes, and run repository validation before closure.

**Acceptance Criteria:**

- Given the completed migration work, when Story 4.3 is completed, then the final package summarizes adopted, adapted, deferred, and excluded items and links them back to `migration-delta-inventory.md`.
- Given the sync checklist, when Story 4.3 is completed, then every in-scope file has a final changed-or-unchanged status and rationale.
- Given repository quality rules, when Story 4.3 is completed, then `make ci` has been run and the validation outcome is captured in the issue or PR review package.
- Given the scope guardrails, when Story 4.3 is completed, then the review summary makes clear that the issue remained documentation-only, did not edit `_bmad/COMMANDS.md` or `_bmad/config.yaml`, and did not rely on wholesale copying from `user-service`.

**Dependencies:** 1.1 through 4.2.

**Changes:**

- Finalize `migration-delta-inventory.md`.
- Finalize `migration-sync-checklist.md`.
- Add or update the final review summary artifact, such as `run-summary.md` or the issue/PR summary.
- Capture the `make ci` result and any residual follow-up items.
