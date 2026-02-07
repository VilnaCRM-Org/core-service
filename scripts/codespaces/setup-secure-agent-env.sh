#!/usr/bin/env bash
set -euo pipefail

# Secure bootstrap for autonomous agent tooling in GitHub Codespaces.
# This script only uses environment variables and does not write secrets to repository files.

readonly DEFAULT_TOKEN_VAR="GH_AUTOMATION_TOKEN"
readonly TOKEN_VAR="${GH_TOKEN_VAR:-$DEFAULT_TOKEN_VAR}"
readonly CODEX_CONFIG="${HOME}/.codex/config.toml"
readonly OPENROUTER_PROFILE_START="# BEGIN CORE-SERVICE OPENROUTER PROFILE"
readonly OPENROUTER_PROFILE_END="# END CORE-SERVICE OPENROUTER PROFILE"

if ! command -v gh >/dev/null 2>&1; then
    echo "Error: gh CLI is required." >&2
    exit 1
fi

if ! command -v codex >/dev/null 2>&1; then
    echo "Error: codex CLI is required." >&2
    exit 1
fi

# Prefer existing gh authentication. Fall back to token env if needed.
if ! gh auth status >/dev/null 2>&1; then
    if [ -z "${GH_TOKEN:-}" ]; then
        if [ -n "${!TOKEN_VAR:-}" ]; then
            export GH_TOKEN="${!TOKEN_VAR}"
        elif [ -n "${GITHUB_TOKEN:-}" ]; then
            export GH_TOKEN="${GITHUB_TOKEN}"
        elif [ -n "${GH_APP_INSTALLATION_TOKEN:-}" ]; then
            export GH_TOKEN="${GH_APP_INSTALLATION_TOKEN}"
        fi
    fi

    if ! gh api user >/dev/null 2>&1; then
        cat >&2 <<'EOM'
Error: GitHub authentication is not available.
If gh is not already authenticated, set one of:
  - GH_TOKEN
  - GH_AUTOMATION_TOKEN
  - GITHUB_TOKEN
  - GH_APP_INSTALLATION_TOKEN
EOM
        exit 1
    fi
fi

gh auth setup-git >/dev/null 2>&1 || true

if [ -z "${OPENROUTER_API_KEY:-}" ]; then
    cat >&2 <<'EOM'
Error: OPENROUTER_API_KEY is not set.
Provide OPENROUTER_API_KEY as a Codespaces secret.
EOM
    exit 1
fi

mkdir -p "$(dirname "${CODEX_CONFIG}")"
touch "${CODEX_CONFIG}"

tmp_without_block="$(mktemp)"
tmp_with_profile="$(mktemp)"
cleanup() {
    rm -f "${tmp_without_block}" "${tmp_with_profile}"
}
trap cleanup EXIT

# Remove previously managed OpenRouter block if present.
awk -v start="${OPENROUTER_PROFILE_START}" -v end="${OPENROUTER_PROFILE_END}" '
    $0 == start {skip=1; next}
    $0 == end {skip=0; next}
    skip == 0 {print}
' "${CODEX_CONFIG}" > "${tmp_without_block}"

# Force default profile to openrouter.
awk '
BEGIN {updated = 0}
/^[[:space:]]*profile[[:space:]]*=/ {
    if (updated == 0) {
        print "profile = \"openrouter\""
        updated = 1
    }
    next
}
{print}
END {
    if (updated == 0) {
        print ""
        print "profile = \"openrouter\""
    }
}
' "${tmp_without_block}" > "${tmp_with_profile}"

cat >> "${tmp_with_profile}" <<'EOM'

# BEGIN CORE-SERVICE OPENROUTER PROFILE
[profiles.openrouter]
model = "openai/gpt-5.2-codex"
model_provider = "openrouter"

[model_providers.openrouter]
name = "OpenRouter"
base_url = "https://openrouter.ai/api/v1"
env_key = "OPENROUTER_API_KEY"
wire_api = "responses"
# END CORE-SERVICE OPENROUTER PROFILE
EOM

mv "${tmp_with_profile}" "${CODEX_CONFIG}"

# Optional git identity from environment for autonomous commits.
if [ -n "${GIT_AUTHOR_NAME:-}" ]; then
    git config --global user.name "${GIT_AUTHOR_NAME}"
fi
if [ -n "${GIT_AUTHOR_EMAIL:-}" ]; then
    git config --global user.email "${GIT_AUTHOR_EMAIL}"
fi

echo "Secure agent environment is ready."
echo "GH auth: available (existing login or token env)."
echo "Codex: configured for OpenRouter profile 'openrouter' with model openai/gpt-5.2-codex."
