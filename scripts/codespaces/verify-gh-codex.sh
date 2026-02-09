#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "Notice: verify-gh-codex.sh is deprecated. Redirecting to verify-gh-opencode.sh..."
exec "${SCRIPT_DIR}/verify-gh-opencode.sh" "$@"
