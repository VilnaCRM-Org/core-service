#!/usr/bin/env bash
set -euo pipefail

# Secure bootstrap for autonomous agent tooling in GitHub Codespaces.
# This script only uses environment variables and does not write secrets to files.

readonly DEFAULT_TOKEN_VAR="GH_AUTOMATION_TOKEN"
readonly TOKEN_VAR="${GH_TOKEN_VAR:-$DEFAULT_TOKEN_VAR}"

if ! command -v gh >/dev/null 2>&1; then
    echo "Error: gh CLI is required." >&2
    exit 1
fi

if ! command -v codex >/dev/null 2>&1; then
    echo "Error: codex CLI is required." >&2
    exit 1
fi

if [ -z "${GH_TOKEN:-}" ]; then
    if [ -n "${!TOKEN_VAR:-}" ]; then
        export GH_TOKEN="${!TOKEN_VAR}"
    elif [ -n "${GITHUB_TOKEN:-}" ]; then
        export GH_TOKEN="${GITHUB_TOKEN}"
    fi
fi

if [ -z "${GH_TOKEN:-}" ]; then
    cat >&2 <<'EOF'
Error: no GitHub token found.
Set one of:
  - GH_TOKEN
  - GH_AUTOMATION_TOKEN (default expected secret)
  - GITHUB_TOKEN
EOF
    exit 1
fi

# Ensure git uses gh for HTTPS auth in this session.
gh auth setup-git >/dev/null

# Optional codex login through API key if not already authenticated.
if ! codex login status >/dev/null 2>&1; then
    if [ -n "${OPENAI_API_KEY:-}" ]; then
        printf '%s' "${OPENAI_API_KEY}" | codex login --with-api-key >/dev/null
    else
        cat >&2 <<'EOF'
Error: codex is not logged in and OPENAI_API_KEY is not set.
Provide OPENAI_API_KEY as a Codespaces secret or run `codex login`.
EOF
        exit 1
    fi
fi

# Optional git identity from environment for autonomous commits.
if [ -n "${GIT_AUTHOR_NAME:-}" ]; then
    git config --global user.name "${GIT_AUTHOR_NAME}"
fi
if [ -n "${GIT_AUTHOR_EMAIL:-}" ]; then
    git config --global user.email "${GIT_AUTHOR_EMAIL}"
fi

echo "Secure agent environment is ready."
echo "GH auth: configured via environment token (not persisted)."
echo "Codex auth: available."
