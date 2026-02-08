#!/usr/bin/env bash
set -euo pipefail

# Secure bootstrap for autonomous agent tooling in GitHub Codespaces.
# This script only uses environment variables and does not write secrets to repository files.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=scripts/codespaces/lib/github-auth.sh
. "${SCRIPT_DIR}/lib/github-auth.sh"

readonly CODEX_CONFIG="${HOME}/.codex/config.toml"
readonly OPENROUTER_PROFILE_START="# BEGIN CORE-SERVICE OPENROUTER PROFILE"
readonly OPENROUTER_PROFILE_END="# END CORE-SERVICE OPENROUTER PROFILE"

cs_require_command gh
cs_require_command codex
cs_ensure_gh_auth

if [ -z "${OPENROUTER_API_KEY:-}" ]; then
    cat >&2 <<'EOM'
Error: OPENROUTER_API_KEY is not set.
Provide OPENROUTER_API_KEY as a Codespaces secret.
EOM
    exit 1
fi

default_profile="openrouter"

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

# Force default profile depending on available credentials.
awk -v profile="${default_profile}" '
BEGIN {updated = 0}
/^[[:space:]]*profile[[:space:]]*=/ {
    if (updated == 0) {
        print "profile = \"" profile "\""
        updated = 1
    }
    next
}
{print}
END {
    if (updated == 0) {
        print ""
        print "profile = \"" profile "\""
    }
}
' "${tmp_without_block}" > "${tmp_with_profile}"

cat >> "${tmp_with_profile}" <<'EOM'

# BEGIN CORE-SERVICE OPENROUTER PROFILE
[profiles.openrouter]
model = "openai/gpt-5.2-codex"
model_provider = "openrouter"
model_reasoning_effort = "xhigh"
model_reasoning_summary = "none"
approval_policy = "never"
sandbox_mode = "danger-full-access"

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
echo "GH auth: available (mode: ${CS_GH_AUTH_MODE:-unknown})."
echo "Codex profile configured:"
echo "  - openrouter: model openai/gpt-5.2-codex via OpenRouter"
echo "    reasoning: xhigh, summaries: none, approvals: never, sandbox: danger-full-access"
echo "Default profile: ${default_profile}"
