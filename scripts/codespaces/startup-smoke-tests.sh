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

: "${OPENCODE_MODEL:=openrouter/openai/gpt-5.2-codex}"

cs_require_command gh
cs_require_command jq
cs_require_command opencode

echo "Running startup smoke tests..."

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

if [ -z "${OPENROUTER_API_KEY:-}" ]; then
    cat >&2 <<'EOM'
Error: OPENROUTER_API_KEY is not set.
Provide OPENROUTER_API_KEY as a Codespaces secret.
EOM
    exit 1
fi

tmp_events=""
tmp_last_text=""
tmp_tool_workspace=""
tmp_tool_token_file=""
tool_token=""
cleanup() {
    [ -n "${tmp_events}" ] && rm -f "${tmp_events}"
    [ -n "${tmp_last_text}" ] && rm -f "${tmp_last_text}"
    [ -n "${tmp_tool_token_file}" ] && rm -f "${tmp_tool_token_file}"
    [ -n "${tmp_tool_workspace}" ] && rm -rf "${tmp_tool_workspace}"
}
trap cleanup EXIT

tmp_events="$(mktemp)"
tmp_last_text="$(mktemp)"
tmp_tool_workspace="$(mktemp -d)"
tmp_tool_token_file="${tmp_tool_workspace}/opencode-startup-token.txt"
tool_token="$(LC_ALL=C tr -dc 'A-Za-z0-9' < /dev/urandom | head -c 24)"
printf '%s\n' "${tool_token}" > "${tmp_tool_token_file}"

echo "Checking OpenCode autonomous tool execution readiness..."
if ! (
    cd "${tmp_tool_workspace}" && timeout 120s opencode run \
        --format json \
        -m "${OPENCODE_MODEL}" \
        "Use the bash tool exactly once and print the content of ./opencode-startup-token.txt. Then reply with exactly one line: opencode-startup-ok:${tool_token}"
) >"${tmp_events}" 2>&1; then
    if grep -qE "invalid_prompt|Invalid Responses API request|ZodError|No matching discriminator" "${tmp_events}"; then
        cat >&2 <<'EOM'
Error: OpenRouter rejected OpenCode tool-calling payloads during startup smoke test.
This blocks autonomous coding flows (edit/test/commit/push).
EOM
    fi
    echo "Error: OpenCode startup smoke test failed." >&2
    sed -n '1,120p' "${tmp_events}" >&2
    exit 1
fi

jq -r 'select(.type == "text" and .part.text != null) | .part.text' "${tmp_events}" > "${tmp_last_text}" || true
if ! grep -qE "^[[:space:]]*opencode-startup-ok:${tool_token}[[:space:]]*$" "${tmp_last_text}"; then
    actual_output="$(tr -d '\r' < "${tmp_last_text}" | tr '\n' ' ' | sed -E 's/[[:space:]]+/ /g; s/^ //; s/ $//')"
    echo "Error: OpenCode startup smoke test returned unexpected output." >&2
    echo "Expected pattern: opencode-startup-ok:${tool_token}" >&2
    echo "Actual trimmed output: ${actual_output}" >&2
    exit 1
fi

echo "OpenCode startup smoke test passed."
echo "Startup smoke tests completed successfully."
