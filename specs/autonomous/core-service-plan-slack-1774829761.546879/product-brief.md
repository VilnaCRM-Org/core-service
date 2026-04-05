# Product Brief: Issue #155 - Migration of Claude Skills from `user-service` to `core-service`

## Problem

`core-service` and `user-service` share a largely similar Claude skill system, but important guidance has drifted across shared files and selected skill directories. `core-service` already embeds BMALPH usage, MongoDB-specific practices, and contributor onboarding flows across multiple repository surfaces. A naive parity copy from `user-service` would risk broken command references, wrong-stack guidance, duplicated concepts, and regression of `core-service`-specific documentation quality.

The immediate need is not a runtime feature. The need is a clear migration plan for documentation and skill-system assets so later implementation work can safely decide what to adopt, what to adapt, and what to exclude.

## Background

Research for issue `#155` shows that `core-service` is already the structural superset of the two repositories’ skill trees. It contains 20 top-level skill directories versus 18 in `user-service`, and there are no top-level skill directories present only in `user-service`. The main differences are content-level deltas in shared guidance files and selected skills, not missing top-level structure.

The highest-signal deltas are in repository guidance files, code review workflow assumptions, OpenAPI-related skill overlap, database-specific performance guidance, and repository-specific examples. `core-service` already treats the skill system as a first-class contributor interface through `AGENTS.md`, `.claude/skills`, `README.md`, `docs/getting-started.md`, and `docs/onboarding.md`. That makes preservation of the current `core-service` baseline critical.

## Deliverable

This stage produces a planning artifact for a documentation and skill-system migration into `core-service`. It does not include production code changes, API behavior changes, runtime features, or infrastructure work.

## Goals

- Define a selective consolidation strategy using `core-service` as the baseline.
- Preserve `core-service` as the source of truth for BMALPH workflows, MongoDB-specific guidance, and the current onboarding path.
- Identify which `user-service` skill content should be adopted as-is, adapted for `core-service`, deferred, or excluded.
- Clarify ambiguous catalog boundaries where overlapping skills could confuse human contributors or AI agents.
- Prepare a brief that can feed a later PRD without reopening baseline scope decisions.

## Non-Goals

- Achieving wholesale parity with the `user-service` skill tree.
- Implementing the documentation migration in this stage.
- Delivering any runtime business capability, API endpoint, or architectural code change.
- Importing commands, examples, or validation flows from `user-service` without `core-service` verification.
- Replacing the existing `core-service` BMALPH setup or onboarding model.

## Stakeholders

- `core-service` maintainers responsible for repository guidance and review quality.
- Contributors and new joiners who rely on onboarding and skill documentation.
- AI coding agents that use `.claude/skills`, `.agents/skills`, and `_bmad` guidance.
- Technical leadership responsible for cross-repository consistency and maintainability.
- `user-service` maintainers as a source of potentially reusable content, not a mandatory parity target.

## User Value

A successful migration plan gives contributors and AI agents a safer, clearer operating model inside `core-service`. They can follow repository guidance with less ambiguity, fewer broken commands, and fewer wrong-stack examples. This reduces onboarding friction, improves reviewability of future skill changes, and protects the repo’s stronger BMALPH-enabled workflow from low-signal documentation churn.

## Scope

**In Scope**

- Compare shared guidance and relevant skill content between `user-service` and `core-service`.
- Define a decision framework for each candidate delta: adopt, adapt, defer, or exclude.
- Plan updates for the core guidance surfaces that may need to stay aligned with any eventual migration.
- Evaluate high-risk overlap areas, especially OpenAPI-related skill taxonomy.
- Define validation expectations for future migration work, including command availability, stack fit, and onboarding consistency.

**Out of Scope**

- Editing application runtime code or service behavior.
- Copying repository-specific examples verbatim from `user-service`.
- Standardizing all repositories around one global skill catalog in this issue.
- Changing `core-service` quality thresholds, CI gates, or architectural rules.
- Expanding the issue into broader contributor-process redesign beyond the migration plan.

## Success Measures

- The later PRD can start from an approved baseline of selective consolidation rather than re-litigating migration strategy.
- Each meaningful content delta is classified with rationale and target disposition.
- No planned migration item depends on commands that do not exist in `core-service`.
- `core-service`-specific BMALPH, MongoDB, and onboarding guidance remains canonical in the plan.
- Catalog ambiguity is reduced by either clarifying or explicitly deferring overlapping skill boundaries.
- Reviewers can treat the planned work as documentation and skill-system migration, not hidden feature scope.

## Assumptions

- The baseline option is selective consolidation into the existing `core-service` skill system.
- `core-service` remains the source of truth for BMALPH guidance, MongoDB-specific operational content, and current onboarding instructions.
- `user-service` examples are candidates for adaptation or exclusion, not direct copy.
- No new top-level skill directory is required solely to mirror `user-service`.
- Stricter policy language from `user-service` is only valuable if it maps to real `core-service` commands and workflows.
- Secondary documentation updates may be required if the eventual migration changes guidance that is already referenced outside `.claude/skills`.

## Risks

- Scope drift from documentation planning into implementation or cross-repo governance work.
- Importing invalid command references such as workflows that do not exist in `core-service`.
- Carrying over wrong-stack guidance, especially where `user-service` content assumes MySQL or other repo-specific behavior.
- Leaving overlapping OpenAPI skill boundaries unresolved, which would preserve contributor confusion.
- Creating high-churn, low-value documentation edits because the two repositories are already close in structure.
- Over-trusting the comparison source, which was based on committed artifacts from a shallow reference clone rather than full history.
