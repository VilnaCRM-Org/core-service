---
name: bmad-autonomous-planning
description: Create BMALPH-wrapped planning artifacts fully autonomously from a short task description. Use when the user wants research, product brief, PRD, architecture, epics, stories, and optional GitHub issue or specs-only PR outputs without human interaction.
---

# BMALPH Autonomous Planning

Use this skill when the user wants BMALPH-style planning without the normal interactive menus.

## Inputs

Expect the caller to provide:

- a short task description
- a bundle id
- a target bundle directory
- a maximum validation round count from `1` to `3`
- GitHub issue mode: `skip` or `create`
- GitHub PR mode: `skip` or `draft`
- base branch and repo slug when GitHub output is requested

## Output Contract

Create a planning bundle under the provided bundle directory with at least:

- `research.md`
- `product-brief.md`
- `product-brief-distillate.md` when it adds value
- `prd.md`
- `architecture.md`
- `epics.md`
- `implementation-readiness.md`
- `run-summary.md`

Your final response must be JSON matching `scripts/local-coder/schemas/autonomous-bmad-planning-result.schema.json`.

## Required Sources

Before planning, load only the sources you need:

1. The BMALPH wrapper command catalog: `_bmad/COMMANDS.md`.
2. The resolved BMAD config file: `_bmad/config.yaml` when present, otherwise `_bmad/bmm/config.yaml`.
3. If both files exist, treat `_bmad/bmm/config.yaml` as optional upstream context only.
4. The BMALPH command wrappers that inform the artifact you are creating:
   - `analyst`
   - `create-brief`
   - `create-prd`
   - `create-architecture`
   - `create-epics-stories`
   - `implementation-readiness`
5. The underlying BMAD source workflows only when the wrapper command entry is insufficient:
   - product brief precedent: `_bmad/bmm/workflows/1-analysis/bmad-product-brief-preview/SKILL.md` and its prompt files
   - PRD validation precedent: `_bmad/bmm/workflows/2-plan-workflows/create-prd/workflow-validate-prd.md`
   - architecture precedent: `_bmad/bmm/workflows/3-solutioning/bmad-create-architecture/workflow.md`
   - epics/stories precedent: `_bmad/bmm/workflows/3-solutioning/bmad-create-epics-and-stories/workflow.md`
   - cross-artifact validation precedent: `_bmad/bmm/workflows/3-solutioning/bmad-check-implementation-readiness/workflow.md`
6. Repository docs that constrain implementation, especially:
   - `AGENTS.md`
   - `docs/design-and-architecture.md`
   - `docs/getting-started.md`
   - `docs/onboarding.md`
   - `docs/developer-guide.md`
7. Relevant code and docs for the requested feature area.

Never bulk-scan the entire repository. Limit yourself to the smallest set of
files and directories that can justify the resulting specs.

Prefer direct reads of the exact wrapper and workflow files listed above. Start
with `_bmad/COMMANDS.md` and only descend into the mapped workflow files when
the wrapper entry does not give enough detail. Do not run broad `rg` searches
over `_bmad/`, `docs/`, or the whole repository unless you first have to
identify one narrow feature-area path.

When a file is long, read only the relevant sections. Avoid dumping full large
files into context if a focused excerpt is enough.

## Core Rule

You are the orchestrator and the user surrogate for this run.

BMALPH command wrappers and their underlying BMAD workflows may say "halt and
wait for user input". Do not stop. Decide the next action yourself using the
task description, repository context, and prior artifacts. Record unresolved
items in `run-summary.md` and `open_questions` instead of blocking.

## Bundle Layout

Use the provided bundle directory directly. Create `validation/` inside it for intermediate review notes when useful.

Keep changes scoped to:

- the bundle directory
- Git metadata and branch state only if GitHub issue or PR output was explicitly requested

Do not implement production code in this run.

## Workflow

### 1. Preflight

- Confirm the bundle directory exists or create it.
- Read `_bmad/COMMANDS.md` first and map the relevant BMALPH wrapper commands for the requested planning run.
- Read the resolved BMAD config file and resolve `planning_artifacts`, `implementation_artifacts`, and `project_knowledge`.
- Infer the feature area from the task description first. Start with the 1-3
  most likely repository paths and expand only if the evidence is insufficient.
- Inspect only the most relevant repository docs and code paths for the requested task.
- Stop discovery once you have enough evidence to cite the repository patterns,
  constraints, and likely implementation surface. Do not keep browsing for
  marginal improvements.
- Write a concise task framing section into `run-summary.md`.

### 2. Context Gathering

Use subagents when available. Give each subagent only the minimum context it needs.

Prefer these sidecar roles:

- repository context analyst
- product/research analyst
- architecture reviewer
- delivery-planning reviewer

If subagents are unavailable, perform the same work sequentially yourself.

Create `research.md` with:

- current-state summary of the repository area involved
- user/problem framing
- constraints and risks
- relevant docs and code references
- assumptions that the main orchestrator had to answer on behalf of the user

### 3. Product Brief

- Follow the BMALPH `create-brief` wrapper first, then the underlying workflow only if needed.
- Create `product-brief.md`.
- Create `product-brief-distillate.md` whenever there is overflow context useful for downstream artifacts.
- Validate and improve the brief for `1..max_validation_rounds` rounds.
- Use reviewer lenses similar to skeptic, opportunity, and contextual review.

### 4. PRD

- Create `prd.md` from the brief, distillate, research, and repository constraints using the BMALPH `create-prd` wrapper as the primary process guide.
- Keep the PRD implementation-ready but not code-level.
- Validate it using the BMAD PRD validation principles:
  - coverage
  - measurability
  - traceability
  - implementation leakage
  - completeness
- Run between `1` and `max_validation_rounds` rounds, stopping early only when the remaining issues are minor or repetitive.

### 5. Architecture

- Create `architecture.md` aligned with the repository’s actual Symfony/API Platform/DDD/hexagonal patterns using the BMALPH `create-architecture` wrapper as the primary process guide.
- Use repository docs and existing code to avoid generic architecture.
- Validate coherence, structure, decision compatibility, and implementation readiness for `1..max_validation_rounds` rounds.

### 6. Epics and Stories

- Create `epics.md` with user-value epics and detailed stories using the BMALPH `create-epics-stories` wrapper as the primary process guide.
- Stories must reference the task’s requirements, architecture constraints, and acceptance criteria.
- Make dependencies strictly forward-safe: no story should depend on a future story.
- Validate epics and stories for `1..max_validation_rounds` rounds.
- Treat story quality as a separate check even if epics and stories live in the same file.

### 7. Cross-Artifact Readiness

- Create `implementation-readiness.md` using the BMALPH `implementation-readiness` wrapper as the primary process guide.
- Verify that brief, PRD, architecture, epics, and stories align.
- Identify any remaining gaps, risks, or open questions.
- Summarize the validation rounds actually used per artifact in `run-summary.md`.

## Decision Policy for Interactive Gates

When a reused BMALPH wrapper or underlying BMAD workflow would normally present a menu:

- treat `A` as "run a deeper review round" when material uncertainty remains
- treat `P` as "use additional subagent perspectives" when those perspectives are likely to change the outcome
- treat `C` as "accept the current artifact state and continue"

Do not loop forever. Hard-stop at the configured validation round limit.

## GitHub Output

Only do this when explicitly requested by the caller.

### Issue Mode `create`

- Create a GitHub issue summarizing the task, bundle contents, recommended implementation plan, risks, and open questions.
- When a trusted launcher is brokering GitHub side effects after the planning run, prepare the bundle and issue-ready summaries, then leave the `github` fields as `skipped` for the launcher to update.
- Prefer GitHub app tools if they are available and authenticated.
- If GitHub app tools are unavailable, use `gh` through a login shell, for example `bash -l -c 'gh issue create ...'`.
- If issue creation fails, do not fail the planning run. Record the failure in the final JSON and `run-summary.md`.

### PR Mode `draft`

- Create a specs-only branch, defaulting to `specs/<bundle-id>`.
- Commit only the planning bundle and any minimal documentation needed to explain it.
- Open a draft PR against the requested base branch.
- When a trusted launcher is brokering GitHub side effects after the planning run, stop after producing the bundle and bundle summaries, then let the launcher create the branch and PR.
- Prefer GitHub app tools if available; otherwise use `gh` in a login shell.
- If PR creation fails, do not fail the planning run. Record the failure in the final JSON and `run-summary.md`.

## Final JSON

Return JSON only. Populate:

- status
- task
- bundle_id
- bundle_dir
- artifacts
- validation_rounds
- open_questions
- warnings
- github

Use `complete-with-warnings` when planning succeeded but GitHub side effects failed or meaningful open questions remain.
