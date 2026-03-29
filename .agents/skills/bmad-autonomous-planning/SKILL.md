---
name: bmad-autonomous-planning
description: >
  Fully autonomous BMALPH planning orchestration for Codex. Use when the user wants specs, validation rounds, and optional GitHub issue or specs-only PR outputs from a short task description without interaction.
---

This wrapper expects local BMAD assets under `_bmad/`. If `_bmad/` is missing, run `make bmalph-setup` first.

Start with `_bmad/COMMANDS.md` as the BMALPH wrapper surface, then read and execute `.claude/skills/bmad-autonomous-planning/SKILL.md`.
