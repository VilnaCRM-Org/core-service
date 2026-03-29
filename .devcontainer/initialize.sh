#!/usr/bin/env bash
set -euo pipefail

# Devcontainer bind mounts require existing host-side paths. Create the
# optional OpenClaw sync directories up front so fresh workspaces start cleanly.
mkdir -p "${HOME}/.openclaw-host-secrets" "${HOME}/.openclaw-host-codex"
chmod 700 "${HOME}/.openclaw-host-secrets" "${HOME}/.openclaw-host-codex"
