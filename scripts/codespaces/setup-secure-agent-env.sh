#!/usr/bin/env bash
set -euo pipefail

# Secure bootstrap for autonomous agent tooling in GitHub Codespaces.
# This script only uses environment variables and does not write secrets to repository files.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../.." && pwd)"
SETTINGS_FILE="${ROOT_DIR}/.devcontainer/codespaces-settings.env"
# shellcheck source=scripts/codespaces/lib/github-auth.sh
. "${SCRIPT_DIR}/lib/github-auth.sh"

if [ -f "${SETTINGS_FILE}" ]; then
    # shellcheck disable=SC1090
    . "${SETTINGS_FILE}"
fi

readonly CODEX_CONFIG="${HOME}/.codex/config.toml"
readonly OPENROUTER_SHIM_PORT="${OPENROUTER_SHIM_PORT:-18082}"
readonly OPENROUTER_SHIM_BIND_HOST="${OPENROUTER_SHIM_BIND_HOST:-127.0.0.1}"
readonly OPENROUTER_PROFILE_START="# BEGIN CORE-SERVICE OPENROUTER PROFILE"
readonly OPENROUTER_PROFILE_END="# END CORE-SERVICE OPENROUTER PROFILE"
: "${CODEX_DEFAULT_PROFILE:=openrouter}"
: "${CODEX_MODEL:=openai/gpt-5.2-codex}"
: "${CODEX_MODEL_PROVIDER:=openrouter}"
: "${CODEX_REASONING_EFFORT:=xhigh}"
: "${CODEX_REASONING_SUMMARY:=none}"
: "${CODEX_APPROVAL_POLICY:=never}"
: "${CODEX_SANDBOX_MODE:=danger-full-access}"
: "${CODEX_PROVIDER_NAME:=OpenRouter}"
: "${CODEX_PROVIDER_WIRE_API:=responses}"
: "${GH_HOST:=github.com}"
: "${GH_GIT_PROTOCOL:=ssh}"
: "${GH_PROMPT:=disabled}"

# Trust model: full-access autonomous mode is expected only for ephemeral Codespaces.
# Outside Codespaces, require explicit opt-in to keep dangerous settings.
if [ "${CODEX_APPROVAL_POLICY}" = "never" ] \
    && [ "${CODEX_SANDBOX_MODE}" = "danger-full-access" ] \
    && [ "${CODESPACES:-}" != "true" ] \
    && [ "${ENABLE_DANGEROUS_AGENT:-}" != "true" ]; then
    echo "Warning: refusing danger-full-access outside Codespaces without ENABLE_DANGEROUS_AGENT=true." >&2
    CODEX_APPROVAL_POLICY="on-request"
    CODEX_SANDBOX_MODE="workspace-write"
fi

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

echo "Starting OpenRouter compatibility shim..."
bash "${SCRIPT_DIR}/start-openrouter-shim.sh"

default_profile="${CODEX_DEFAULT_PROFILE}"

mkdir -p "$(dirname "${CODEX_CONFIG}")"
touch "${CODEX_CONFIG}"

tmp_without_block=""
tmp_with_profile=""

validate_toml_scalar_env() {
    local var_name="$1"
    local var_value="$2"

    case "${var_value}" in
        *$'\n'*|*$'\r'*|*\"*|*\\*)
            echo "Error: ${var_name} contains unsupported characters for TOML scalar interpolation." >&2
            echo "Use a value without newlines, double quotes, or backslashes." >&2
            exit 1
            ;;
    esac
}

cleanup() {
    [ -n "${tmp_without_block}" ] && rm -f "${tmp_without_block}"
    [ -n "${tmp_with_profile}" ] && rm -f "${tmp_with_profile}"
}
trap cleanup EXIT

tmp_without_block="$(mktemp)"
tmp_with_profile="$(mktemp)"

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

for toml_env in \
    CODEX_MODEL \
    CODEX_MODEL_PROVIDER \
    CODEX_REASONING_EFFORT \
    CODEX_REASONING_SUMMARY \
    CODEX_APPROVAL_POLICY \
    CODEX_SANDBOX_MODE \
    CODEX_PROVIDER_NAME \
    CODEX_PROVIDER_WIRE_API \
    OPENROUTER_SHIM_BIND_HOST \
    OPENROUTER_SHIM_PORT; do
    validate_toml_scalar_env "${toml_env}" "${!toml_env}"
done

cat >> "${tmp_with_profile}" <<EOM

# BEGIN CORE-SERVICE OPENROUTER PROFILE
[profiles.openrouter]
model = "${CODEX_MODEL}"
model_provider = "${CODEX_MODEL_PROVIDER}"
model_reasoning_effort = "${CODEX_REASONING_EFFORT}"
model_reasoning_summary = "${CODEX_REASONING_SUMMARY}"
# WARNING: this profile can execute shell commands without approval.
# It is intended for trusted, ephemeral GitHub Codespaces automation only.
approval_policy = "${CODEX_APPROVAL_POLICY}"
sandbox_mode = "${CODEX_SANDBOX_MODE}"

[model_providers.openrouter]
name = "${CODEX_PROVIDER_NAME}"
base_url = "http://${OPENROUTER_SHIM_BIND_HOST}:${OPENROUTER_SHIM_PORT}/api/v1"
env_key = "OPENROUTER_API_KEY"
wire_api = "${CODEX_PROVIDER_WIRE_API}"
# END CORE-SERVICE OPENROUTER PROFILE
EOM

mv "${tmp_with_profile}" "${CODEX_CONFIG}"

# Persist repository-defined GH defaults for CLI behavior in each fresh Codespace.
gh config set git_protocol "${GH_GIT_PROTOCOL}" --host "${GH_HOST}" >/dev/null 2>&1 || true
gh config set prompt "${GH_PROMPT}" >/dev/null 2>&1 || true

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
echo "  - ${default_profile}: model ${CODEX_MODEL} via ${CODEX_PROVIDER_NAME}"
echo "    reasoning: ${CODEX_REASONING_EFFORT}, summaries: ${CODEX_REASONING_SUMMARY}, approvals: ${CODEX_APPROVAL_POLICY}, sandbox: ${CODEX_SANDBOX_MODE}"
echo "    transport: local OpenRouter compatibility shim on http://${OPENROUTER_SHIM_BIND_HOST}:${OPENROUTER_SHIM_PORT}"
echo "Default profile: ${default_profile}"
