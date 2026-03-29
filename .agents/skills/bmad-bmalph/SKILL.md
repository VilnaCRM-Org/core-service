---
name: bmalph
description: >
  BMAD master agent — navigate phases. Use when the user asks about bmalph.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first, or `bmalph upgrade --force` if BMALPH is already installed for this repo.
First consult `.claude/skills/AI-AGENT-GUIDE.md` and `.claude/skills/SKILL-DECISION-GUIDE.md` to determine the correct approach before proceeding.

Read and execute the workflow/task at `_bmad/core/skills/bmad-help/workflow.md`.
