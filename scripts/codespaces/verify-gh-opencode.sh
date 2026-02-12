#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "Notice: OpenCode verification is removed. Redirecting to verify-gh-codex.sh (Codex + Claude Code checks)."
exec "${SCRIPT_DIR}/verify-gh-codex.sh" "$@"
