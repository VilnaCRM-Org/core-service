#!/usr/bin/env bash
set -euo pipefail

ORG="${1:-${CODESPACE_GITHUB_ORG:-VilnaCRM-Org}}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../.." && pwd)"
SETTINGS_FILE="${ROOT_DIR}/.devcontainer/codespaces-settings.env"
# shellcheck source=scripts/codespaces/lib/github-auth.sh
. "${SCRIPT_DIR}/lib/github-auth.sh"

if [ -f "${SETTINGS_FILE}" ]; then
    # shellcheck disable=SC1090
    . "${SETTINGS_FILE}"
fi

if [ -f "${HOME}/.config/core-service/agent-secrets.env" ]; then
    # shellcheck disable=SC1091
    . "${HOME}/.config/core-service/agent-secrets.env"
fi

: "${CODEX_PROFILE_NAME:=openai}"
: "${CODEX_MODEL:=gpt-5.2-codex}"
: "${CODEX_REASONING_EFFORT:=medium}"
: "${CODEX_APPROVAL_POLICY:=never}"
: "${CODEX_SANDBOX_MODE:=danger-full-access}"

cs_require_command gh
cs_require_command codex
cs_require_command bats

echo "Running startup smoke tests..."

echo "Checking Bats availability..."
bats --version

echo "Checking GitHub authentication..."
cs_ensure_gh_auth

echo "Checking repository listing for org '${ORG}'..."
repo_count="$(gh repo list "${ORG}" --limit 1 --json name --jq 'length' 2>/dev/null || true)"
if ! [[ "${repo_count:-}" =~ ^[0-9]+$ ]]; then
    repo_count=0
fi
if [ "${repo_count}" -lt 1 ]; then
    echo "Error: unable to list repositories for org '${ORG}'." >&2
    exit 1
fi
echo "GitHub CLI smoke test passed."

if [ -z "${OPENAI_API_KEY:-}" ]; then
    cat >&2 <<'EOM'
Error: OPENAI_API_KEY is not set.
Provide OPENAI_API_KEY as a Codespaces secret.
EOM
    exit 1
fi

echo "Checking Codex profile configuration..."
if [ ! -f "${HOME}/.codex/config.toml" ]; then
    echo "Error: Codex config file is missing: ${HOME}/.codex/config.toml" >&2
    exit 1
fi
if ! grep -q "profile = \"${CODEX_PROFILE_NAME}\"" "${HOME}/.codex/config.toml"; then
    echo "Error: Codex default profile '${CODEX_PROFILE_NAME}' is not configured." >&2
    exit 1
fi
if ! grep -q "model = \"${CODEX_MODEL}\"" "${HOME}/.codex/config.toml"; then
    echo "Error: Codex default model '${CODEX_MODEL}' is not configured." >&2
    exit 1
fi
if ! grep -q "model_reasoning_effort = \"${CODEX_REASONING_EFFORT}\"" "${HOME}/.codex/config.toml"; then
    echo "Error: Codex reasoning effort '${CODEX_REASONING_EFFORT}' is not configured." >&2
    exit 1
fi
if ! grep -q "approval_policy = \"${CODEX_APPROVAL_POLICY}\"" "${HOME}/.codex/config.toml"; then
    echo "Error: Codex approval policy '${CODEX_APPROVAL_POLICY}' is not configured." >&2
    exit 1
fi
if ! grep -q "sandbox_mode = \"${CODEX_SANDBOX_MODE}\"" "${HOME}/.codex/config.toml"; then
    echo "Error: Codex sandbox mode '${CODEX_SANDBOX_MODE}' is not configured." >&2
    exit 1
fi

echo "Running Codex smoke task via profile '${CODEX_PROFILE_NAME}'..."
tmp_codex_output=""
cleanup() {
    [ -n "${tmp_codex_output}" ] && rm -f "${tmp_codex_output}"
}
trap cleanup EXIT

tmp_codex_output="$(mktemp)"
if ! timeout 180s codex exec -p "${CODEX_PROFILE_NAME}" "Reply with exactly one line: codex-startup-ok" >"${tmp_codex_output}" 2>&1; then
    echo "Error: Codex smoke task failed." >&2
    sed -n '1,120p' "${tmp_codex_output}" >&2
    exit 1
fi

if ! grep -q "codex-startup-ok" "${tmp_codex_output}"; then
    echo "Error: Codex smoke task did not return expected output." >&2
    sed -n '1,120p' "${tmp_codex_output}" >&2
    exit 1
fi

echo "Codex startup smoke test passed."
echo "Startup smoke tests completed successfully."
