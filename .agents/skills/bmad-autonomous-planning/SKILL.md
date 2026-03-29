---
name: bmad-autonomous-planning
description: >
  Fully autonomous BMALPH planning orchestration for Codex. Use when the user wants specs, validation rounds, and optional GitHub issue or specs-only PR outputs from a short task description without interaction.
---

This wrapper expects local BMAD assets under `_bmad/`. If `_bmad/` is missing, run `make bmalph-setup` first.

For top-level Codex work in this repository, prefer the launcher entrypoint instead of manually replaying the planning flow in the current session.

Primary command:

```bash
make bmalph-autonomous-plan \
  PLAN_TASK="Plan a new feature" \
  PLAN_VALIDATION_ROUNDS=2
```

Useful variants:

```bash
# Safe preview of paths, bundle id, and resolved inputs only
make bmalph-autonomous-plan \
  PLAN_TASK="Plan a new feature" \
  PLAN_VALIDATION_ROUNDS=2 \
  PLAN_DRY_RUN=true

# Stream safe BMALPH progress trace to stderr during the live run
make bmalph-autonomous-plan \
  PLAN_TASK="Plan a new feature" \
  PLAN_VALIDATION_ROUNDS=2 \
  PLAN_DEBUG=true

# Persist the final machine-readable JSON so Codex can inspect it after the run
make bmalph-autonomous-plan \
  PLAN_TASK="Plan a new feature" \
  PLAN_VALIDATION_ROUNDS=2 \
  PLAN_RESULT_FILE="$(mktemp)"
```

Launcher contract:

- `PLAN_TASK` is required.
- `PLAN_VALIDATION_ROUNDS` accepts `1` to `3`.
- `PLAN_DRY_RUN=true` performs a non-mutating preview.
- `PLAN_DEBUG=true` streams a safe trace to `stderr`; the final JSON still goes to `stdout`.
- `PLAN_RESULT_FILE=/abs/path.json` writes the same final JSON to disk for follow-up inspection.
- `PLAN_ISSUE_MODE=create` and `PLAN_PR_MODE=draft` request trusted GitHub side effects after the planning run.
- `PLAN_REPO`, `PLAN_BASE_BRANCH`, and `PLAN_MODEL` are optional overrides.

After the launcher finishes:

1. Read the JSON result.
2. Review the bundle directory and listed artifacts.
3. Only fall back to manual workflow execution if the user explicitly asks to bypass the launcher or the launcher cannot run in the current environment.

When you do need the internal planning contract, start with `_bmad/COMMANDS.md` as the BMALPH wrapper surface, then read and execute `.claude/skills/bmad-autonomous-planning/SKILL.md`.
