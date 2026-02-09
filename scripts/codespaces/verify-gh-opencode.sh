#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../.." && pwd)"
SETTINGS_FILE="${ROOT_DIR}/.devcontainer/codespaces-settings.env"
# shellcheck source=scripts/codespaces/lib/github-auth.sh
. "${SCRIPT_DIR}/lib/github-auth.sh"

if [ -f "${SETTINGS_FILE}" ]; then
    # shellcheck disable=SC1090
    . "${SETTINGS_FILE}"
fi

ORG="${1:-${CODESPACE_GITHUB_ORG:-VilnaCRM-Org}}"
: "${OPENCODE_MODEL:=openrouter/openai/gpt-5.2-codex}"

cs_require_command gh
cs_require_command jq
cs_require_command opencode

echo "Checking GitHub authentication..."
cs_ensure_gh_auth

echo "Checking GitHub token scopes (if available)..."
scopes_headers="$(gh api -i /user 2>/dev/null || true)"
scopes="$(
    printf '%s' "${scopes_headers}" \
        | tr -d '\r' \
        | awk -F': ' 'tolower($1)=="x-oauth-scopes"{print $2; exit}'
)"

if [ -n "${scopes}" ]; then
    echo "Available token scopes: ${scopes}"
    normalized_scopes="$(echo "${scopes}" | tr -d ' ')"
    for required_scope in repo read:org; do
        if [[ ",${normalized_scopes}," != *",${required_scope},"* ]]; then
            echo "Warning: expected scope '${required_scope}' is missing." >&2
        fi
    done
else
    echo "Note: scope header unavailable for this token."
fi

echo "Listing repositories in org '${ORG}'..."
repo_count="$(gh repo list "${ORG}" --limit 1000 --json name --jq 'length' 2>/dev/null || true)"
if ! [[ "${repo_count:-}" =~ ^[0-9]+$ ]]; then
    repo_count=0
fi
if [ "${repo_count}" -le 0 ]; then
    echo "Error: failed to list repositories for org '${ORG}'." >&2
    exit 1
fi
echo "Repository listing ok (${repo_count} repositories visible)."

echo "Checking current PR CI status..."
pr_number="$(gh pr view --json number --jq '.number' 2>/dev/null || true)"
if [ -n "${pr_number}" ]; then
    checks_json="$(gh pr checks "${pr_number}" --json name,state 2>/dev/null)" || {
        cat >&2 <<EOM
Error: failed to query checks for PR #${pr_number}.
Ensure your authentication can read pull request checks/actions metadata for this repository.
EOM
        exit 1
    }
    if ! non_success_count="$(
        printf '%s' "${checks_json}" \
            | jq '[.[].state | select(. != "SUCCESS" and . != "SKIPPED" and . != "NEUTRAL")] | length'
    )"; then
        cat >&2 <<EOM
Error: failed to parse PR checks JSON for PR #${pr_number}.
Received payload was not valid JSON.
EOM
        exit 1
    fi
    echo "PR #${pr_number} checks query ok (non-success states: ${non_success_count})."
else
    echo "No PR detected for current branch. Skipping PR checks."
fi

echo "Checking git push permissions on current branch..."
current_branch="$(git symbolic-ref --quiet --short HEAD 2>/dev/null || true)"
if [ -z "${current_branch}" ]; then
    cat >&2 <<'EOM'
Error: current git checkout is in detached HEAD state.
Check out a branch before running push verification.
EOM
    exit 1
fi
if ! git push --dry-run origin "${current_branch}" >/dev/null; then
    cat >&2 <<EOM
Error: git push dry-run failed for branch '${current_branch}'.
Ensure your token has write permissions for repository contents.
EOM
    exit 1
fi
echo "Git push dry-run ok for branch '${current_branch}'."

if [ -z "${OPENROUTER_API_KEY:-}" ]; then
    cat >&2 <<'EOM'
Error: OPENROUTER_API_KEY is not set.
Provide OPENROUTER_API_KEY to verify OpenCode OpenRouter execution.
EOM
    exit 1
fi

tmp_basic_events=""
tmp_basic_text=""
tmp_tool_events=""
tmp_tool_text=""
tmp_tool_workspace=""
tmp_tool_token_file=""
tool_token=""
cleanup() {
    [ -n "${tmp_basic_events}" ] && rm -f "${tmp_basic_events}"
    [ -n "${tmp_basic_text}" ] && rm -f "${tmp_basic_text}"
    [ -n "${tmp_tool_events}" ] && rm -f "${tmp_tool_events}"
    [ -n "${tmp_tool_text}" ] && rm -f "${tmp_tool_text}"
    [ -n "${tmp_tool_token_file}" ] && rm -f "${tmp_tool_token_file}"
    [ -n "${tmp_tool_workspace}" ] && rm -rf "${tmp_tool_workspace}"
}
trap cleanup EXIT

tmp_basic_events="$(mktemp)"
tmp_basic_text="$(mktemp)"
tmp_tool_events="$(mktemp)"
tmp_tool_text="$(mktemp)"
tmp_tool_workspace="$(mktemp -d)"
tmp_tool_token_file="${tmp_tool_workspace}/opencode-tools-token.txt"
if command -v uuidgen >/dev/null 2>&1; then
    tool_token="$(uuidgen | tr '[:upper:]' '[:lower:]' | tr -d '-')"
else
    tool_token="$(tr -d '-' < /proc/sys/kernel/random/uuid)"
fi
printf '%s\n' "${tool_token}" > "${tmp_tool_token_file}"

echo "Running OpenCode smoke task via OpenRouter..."
if ! timeout 120s opencode run \
    --format json \
    -m "${OPENCODE_MODEL}" \
    "Reply with exactly one line: opencode-ok:openrouter-basic" \
    >"${tmp_basic_events}" 2>&1; then
    echo "Error: OpenCode smoke task execution failed." >&2
    echo "OpenCode output:" >&2
    sed -n '1,120p' "${tmp_basic_events}" >&2
    exit 1
fi

jq -r 'select(.type == "text" and .part.text != null) | .part.text' "${tmp_basic_events}" > "${tmp_basic_text}" || true
if ! grep -q '^opencode-ok:' "${tmp_basic_text}"; then
    echo "Error: OpenCode smoke task did not return expected output." >&2
    echo "Text events:" >&2
    cat "${tmp_basic_text}" >&2
    exit 1
fi

echo "OpenCode basic smoke task ok: $(tail -n 1 "${tmp_basic_text}")"
echo "Running OpenCode tool-calling smoke task (autonomous mode)..."

if ! (
    cd "${tmp_tool_workspace}" && timeout 180s opencode run \
        --format json \
        -m "${OPENCODE_MODEL}" \
        "Use the bash tool exactly once and print the content of ./opencode-tools-token.txt. Then reply with exactly one line: opencode-ok:openrouter-tools:${tool_token}"
) >"${tmp_tool_events}" 2>&1; then
    if grep -qE "ZodError|invalid_prompt|Invalid Responses API request|No matching discriminator" "${tmp_tool_events}"; then
        cat >&2 <<'EOM'
Error: OpenRouter rejected OpenCode tool-calling payloads.
Result: prompt-only OpenCode works, but autonomous coding actions (edit/refactor/test/commit flows) are blocked.
EOM
    else
        echo "Error: OpenCode tool-calling smoke task failed." >&2
    fi
    echo "OpenCode output:" >&2
    sed -n '1,160p' "${tmp_tool_events}" >&2
    exit 1
fi

jq -r 'select(.type == "text" and .part.text != null) | .part.text' "${tmp_tool_events}" > "${tmp_tool_text}" || true
if ! grep -qE "^opencode-ok:openrouter-tools:${tool_token}[[:space:]]*$" "${tmp_tool_text}"; then
    echo "Error: OpenCode tool-calling smoke task did not return expected output." >&2
    echo "Expected pattern: opencode-ok:openrouter-tools:${tool_token}" >&2
    echo "Text events:" >&2
    cat "${tmp_tool_text}" >&2
    exit 1
fi

echo "OpenCode tool-calling smoke task ok: $(tail -n 1 "${tmp_tool_text}")"
echo "All GH/OpenCode verification checks passed."
