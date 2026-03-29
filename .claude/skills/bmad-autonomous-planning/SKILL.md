---
name: bmad-autonomous-planning
description: Create BMALPH planning artifacts autonomously from a short task description by delegating each planning phase to a focused subagent and orchestrating the handoffs without human interaction.
---

# BMALPH Autonomous Planning

Use this skill when the user wants BMALPH-style planning from a short prompt but
does not want to walk through interactive menus step by step.

## Non-Negotiable Rules

- Run the planning flow in the current AI session. Do not depend on repo-local
  bash wrappers, `make` targets, or other launcher automation.
- Use BMALPH as the primary process surface: start with `_bmad/COMMANDS.md`,
  then descend into the specific wrapper workflow files only when the command
  catalog is not enough.
- Spawn one focused subagent per BMALPH planning stage when subagents are
  available. Do not overload a single subagent with the whole planning flow.
- The main agent is the user surrogate. If BMALPH asks for approval, choices, or
  clarification, decide on the user's behalf, continue, and record open
  questions instead of blocking.
- Do not implement production code during this skill. Produce specs only.

## Inputs

Expect the caller to provide:

- a short task description
- an optional bundle id or target bundle directory
- an optional validation round limit from `1` to `3`
- optional GitHub issue or specs-only PR output requirements

If the caller does not provide a bundle directory, derive one under the
configured planning artifacts path, for example
`<planning_artifacts>/autonomous/<timestamp>-<task-slug>`.

## Output Bundle

Create a planning bundle with at least:

- `research.md`
- `product-brief.md`
- `product-brief-distillate.md` when it adds value
- `prd.md`
- `architecture.md`
- `epics.md`
- `implementation-readiness.md`
- `run-summary.md`

`run-summary.md` must also contain:

- the chosen bundle directory
- a `Subagent Execution Log` section listing the phase, wrapper command, and
  artifact owned by each subagent
- the validation rounds used per artifact
- open questions, warnings, blockers, and the recommended next step

The final assistant response should summarize:

- bundle directory
- artifact paths
- validation rounds used
- remaining open questions or warnings
- GitHub issue/PR status when requested

## Required Sources

Load only the minimum sources required for the current stage:

1. `_bmad/COMMANDS.md`
2. The resolved BMAD config file:
   - `_bmad/config.yaml` when present
   - otherwise `_bmad/bmm/config.yaml`
   - if both exist, treat `_bmad/bmm/config.yaml` as optional upstream context
3. The BMALPH wrapper entries and workflow files for:
   - `analyst`
     - `_bmad/bmm/agents/analyst.agent.yaml`
   - `create-brief`
     - `_bmad/bmm/workflows/1-analysis/bmad-create-product-brief/workflow.md`
     - `_bmad/bmm/workflows/1-analysis/bmad-create-product-brief/steps/step-01-init.md`
   - `create-prd`
     - `_bmad/bmm/agents/pm.agent.yaml`
     - `_bmad/core/tasks/bmad-create-prd/workflow.md`
     - `_bmad/core/tasks/bmad-create-prd/steps-c/step-01-init.md`
   - `create-architecture`
     - `_bmad/bmm/agents/architect.agent.yaml`
     - `_bmad/bmm/workflows/3-solutioning/bmad-create-architecture/workflow.md`
     - `_bmad/bmm/workflows/3-solutioning/bmad-create-architecture/steps/step-01-init.md`
   - `create-epics-stories`
     - `_bmad/bmm/workflows/3-solutioning/bmad-create-epics-and-stories/workflow.md`
     - `_bmad/bmm/workflows/3-solutioning/bmad-create-epics-and-stories/steps/step-01-validate-prerequisites.md`
   - `implementation-readiness`
     - `_bmad/bmm/workflows/3-solutioning/bmad-check-implementation-readiness/workflow.md`
     - `_bmad/bmm/workflows/3-solutioning/bmad-check-implementation-readiness/steps/step-01-document-discovery.md`
4. Repository guidance that constrains implementation, especially:
   - `AGENTS.md`
   - `docs/design-and-architecture.md`
   - `docs/getting-started.md`
   - `docs/onboarding.md`
   - `docs/developer-guide.md`
5. Only the feature-area code and docs needed to justify the plan

Never bulk-scan the whole repository when a narrow set of files will do.

## Main-Agent Responsibilities

The main agent owns orchestration and artifact quality. It must:

1. Resolve the bundle path and initialize the planning run.
2. Decide which repository files each subagent needs.
3. Spawn the stage subagent with only the minimum context required.
4. Review the returned draft before moving to the next stage.
5. Answer workflow questions on behalf of the user.
6. Decide whether another validation round is necessary.
7. Maintain continuity across stages so the next subagent sees the correct
   upstream artifact set.
8. Update the `Subagent Execution Log` in `run-summary.md` after every phase.

## Subagent Contract

For every BMALPH stage, the main agent should hand the subagent:

- the specific BMALPH wrapper command(s) or workflow file(s) it must follow
- the current task framing
- only the upstream artifacts required for that stage
- only the repository files needed for evidence
- a clear output contract:
  - artifact draft content
  - key assumptions made
  - unresolved questions
  - validation findings or concerns

Every subagent should return a draft plus findings, not a request to pause for a
human.

## Workflow

### 1. Preflight

- Resolve the BMAD config and `planning_artifacts` directory.
- Create the bundle directory if needed.
- Read `_bmad/COMMANDS.md` and map the BMALPH stages relevant to this planning run.
- Infer the most likely feature-area paths from the task description before any
  broad discovery.
- Write a short task framing section into `run-summary.md`.

### 2. Research Stage

Spawn a research subagent aligned to the `analyst` role.

The research subagent should:

- inspect the most relevant docs and code paths
- summarize current-state behavior and constraints
- identify implementation risks and likely surface area
- return a draft for `research.md`

The main agent then reviews the result, resolves open choices, and finalizes
`research.md`.

### 3. Product Brief Stage

Spawn a brief subagent aligned to `create-brief`.

Inputs:

- task description
- `research.md`
- only the wrapper/workflow files needed for the product brief stage

Outputs:

- draft `product-brief.md`
- optional `product-brief-distillate.md`
- explicit gaps, risks, and questions

The main agent must review the draft before moving on.

### 4. PRD Stage

Spawn a PRD subagent aligned to `create-prd`.

Inputs:

- `research.md`
- `product-brief.md`
- `product-brief-distillate.md` when present

The PRD subagent should produce an implementation-ready but not code-level
`prd.md`. The main agent validates coverage, measurability, traceability, and
completeness before proceeding.

### 5. Architecture Stage

Spawn an architecture subagent aligned to `create-architecture`.

Inputs:

- `research.md`
- `prd.md`
- repository architecture guidance and relevant feature-area code

The architecture must fit the repository's actual Symfony, API Platform, DDD,
CQRS, and hexagonal patterns. The main agent validates compatibility and
implementation readiness before moving on. If the PRD is not strong enough, do
not improvise; send the flow back to PRD refinement first.

### 6. Epics and Stories Stage

Spawn an epics/stories subagent aligned to `create-epics-stories`.

Inputs:

- `prd.md`
- `architecture.md`
- relevant constraints from `research.md`

The subagent should produce forward-safe epics and actionable stories in
`epics.md`. The main agent separately reviews story quality, dependency order,
and acceptance-criteria coverage.

### 7. Cross-Artifact Readiness Stage

Spawn a readiness subagent aligned to `implementation-readiness`.

Inputs:

- `product-brief.md`
- `prd.md`
- `architecture.md`
- `epics.md`

This subagent should identify gaps, inconsistencies, and unresolved planning
risks in `implementation-readiness.md`. The main agent finalizes the readiness
assessment and updates `run-summary.md`.

## Validation Loop

Use `1` to `3` validation rounds per artifact.

For each artifact, the main agent may:

- accept the draft
- revise it directly
- spawn a reviewer subagent for another pass

Stop early when only minor or repetitive issues remain. Do not loop endlessly.

## Decision Policy for Interactive BMALPH Gates

If a BMALPH wrapper or workflow expects user input:

- continue without asking the human
- choose the best option based on task intent and repository evidence
- prefer another review round when uncertainty is material
- record unresolved concerns in `run-summary.md` and `implementation-readiness.md`

If a phase is blocked by a genuinely missing prerequisite, stop only that phase,
record the blocker explicitly, and do not fabricate the missing input.

Use the BMALPH menu concepts as policy, not as a hard stop:

- deeper review when the artifact is still weak
- extra perspective when another subagent is likely to add signal
- continue when the artifact is ready enough for the next stage

## GitHub Output

Only create a GitHub issue or specs-only PR when the user explicitly asks.

When requested:

- finish the planning bundle first
- create GitHub side effects only after the artifacts are stable
- prefer GitHub app tools when available
- fall back to `gh` only when necessary
- record failures as warnings instead of discarding the planning bundle
