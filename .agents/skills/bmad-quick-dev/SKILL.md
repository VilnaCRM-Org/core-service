---
name: quick-dev
description: >
  Quick one-off tasks small changes simple apps utilities without extensive planning - Do not suggest for potentially very complex things unless requested or if the user complains that they do not want to follow the extensive planning of the bmad method, unless the user is already working through the implementation phase and just requests a 1 off things not already in the plan. Use when the user asks about quick dev.
metadata:
  managed-by: bmalph
---

This wrapper requires local BMALPH assets under `_bmad/`, which this repository intentionally keeps out of git.
If `_bmad/` is missing in a fresh clone or workspace, run `make bmalph-setup` first, or `bmalph upgrade --force` if BMALPH is already installed for this repo.

Adopt the role of the agent defined in `_bmad/bmm/agents/quick-flow-solo-dev.agent.yaml`, then read and execute the workflow at `_bmad/bmm/workflows/bmad-quick-flow/bmad-quick-dev/workflow.md` in Create mode.
