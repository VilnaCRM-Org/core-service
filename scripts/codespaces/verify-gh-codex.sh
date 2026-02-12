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

if [ -f "${HOME}/.config/core-service/agent-secrets.env" ]; then
    # shellcheck disable=SC1091
    . "${HOME}/.config/core-service/agent-secrets.env"
fi

ORG="${1:-${CODESPACE_GITHUB_ORG:-VilnaCRM-Org}}"
: "${CODEX_PROFILE_NAME:=openrouter}"
: "${CODEX_TOOL_SMOKE_MODE:=auto}"
: "${CLAUDE_DEFAULT_MODEL:=anthropic/claude-sonnet-4.5}"
: "${CLAUDE_PERMISSION_MODE:=bypassPermissions}"

cs_require_command gh
cs_require_command jq
cs_require_command codex
cs_require_command claude
cs_require_command bats

echo "Checking GitHub authentication..."
cs_ensure_gh_auth

echo "Checking GitHub token scopes (if available)..."
scopes_headers="$(gh api -i /user 2>/dev/null || true)"
scopes="$({
    printf '%s' "${scopes_headers}" \
        | tr -d '\r' \
        | awk -F': ' 'tolower($1)=="x-oauth-scopes"{print $2; exit}'
} || true)"

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
repo_count="$(gh repo list "${ORG}" --limit 1 --json name --jq 'length' 2>/dev/null || true)"
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
    non_success_count="$(printf '%s' "${checks_json}" | jq '[.[].state | select(. != "SUCCESS" and . != "SKIPPED" and . != "NEUTRAL")] | length')" || {
        cat >&2 <<EOM
Error: failed to parse PR checks JSON for PR #${pr_number}.
Received payload was not valid JSON.
EOM
        exit 1
    }
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
if ! git push --dry-run origin "${current_branch}" >/dev/null 2>&1; then
    cat >&2 <<EOM
Error: git push dry-run failed for branch '${current_branch}'.
Ensure your token has write permissions for repository contents.
EOM
    exit 1
fi
echo "Git push dry-run ok for branch '${current_branch}'."

echo "Checking Bats availability..."
bats --version

if [ -z "${OPENROUTER_API_KEY:-}" ]; then
    cat >&2 <<'EOM'
Error: OPENROUTER_API_KEY is not set.
Provide OPENROUTER_API_KEY as a Codespaces secret.
EOM
    exit 1
fi

if [ -z "${ANTHROPIC_AUTH_TOKEN:-}" ] || [ -z "${ANTHROPIC_BASE_URL:-}" ]; then
    cat >&2 <<'EOM'
Error: Claude OpenRouter environment is not configured.
Run: bash scripts/codespaces/setup-secure-agent-env.sh
EOM
    exit 1
fi

if [ ! -f "${HOME}/.claude/settings.json" ]; then
    echo "Error: Claude settings missing: ${HOME}/.claude/settings.json" >&2
    exit 1
fi
configured_claude_model="$(jq -r '.model // empty' "${HOME}/.claude/settings.json" 2>/dev/null || true)"
if [ "${configured_claude_model}" != "${CLAUDE_DEFAULT_MODEL}" ]; then
    echo "Error: Claude default model mismatch. Expected '${CLAUDE_DEFAULT_MODEL}', got '${configured_claude_model:-<empty>}'" >&2
    exit 1
fi
configured_claude_permission_mode="$(jq -r '.permissions.defaultMode // empty' "${HOME}/.claude/settings.json" 2>/dev/null || true)"
if [ "${configured_claude_permission_mode}" != "${CLAUDE_PERMISSION_MODE}" ]; then
    echo "Error: Claude default permission mode mismatch. Expected '${CLAUDE_PERMISSION_MODE}', got '${configured_claude_permission_mode:-<empty>}'" >&2
    exit 1
fi

tmp_codex_basic=""
tmp_codex_tools=""
tmp_claude_basic=""
tmp_claude_tools=""
tmp_tool_workspace=""
tmp_tool_marker_file=""
tool_marker=""
cleanup() {
    [ -n "${tmp_codex_basic}" ] && rm -f "${tmp_codex_basic}"
    [ -n "${tmp_codex_tools}" ] && rm -f "${tmp_codex_tools}"
    [ -n "${tmp_claude_basic}" ] && rm -f "${tmp_claude_basic}"
    [ -n "${tmp_claude_tools}" ] && rm -f "${tmp_claude_tools}"
    [ -n "${tmp_tool_marker_file}" ] && rm -f "${tmp_tool_marker_file}"
    [ -n "${tmp_tool_workspace}" ] && rm -rf "${tmp_tool_workspace}"
}
trap cleanup EXIT

tmp_codex_basic="$(mktemp)"
tmp_codex_tools="$(mktemp)"
tmp_claude_basic="$(mktemp)"
tmp_claude_tools="$(mktemp)"
tmp_tool_workspace="$(mktemp -d)"
tmp_tool_marker_file="${tmp_tool_workspace}/codex-tools-marker.txt"
if command -v uuidgen >/dev/null 2>&1; then
    tool_marker="$(uuidgen | tr '[:upper:]' '[:lower:]' | tr -d '-')"
else
    tool_marker="$(tr -d '-' < /proc/sys/kernel/random/uuid)"
fi

echo "Running Codex basic smoke task via OpenRouter..."
if ! timeout 180s codex exec -p "${CODEX_PROFILE_NAME}" --dangerously-bypass-approvals-and-sandbox "Reply with exactly one line: codex-ok:openrouter-basic" >"${tmp_codex_basic}" 2>&1; then
    echo "Error: Codex basic smoke task failed." >&2
    sed -n '1,120p' "${tmp_codex_basic}" >&2
    exit 1
fi
if ! grep -q "codex-ok:openrouter-basic" "${tmp_codex_basic}"; then
    echo "Error: Codex basic smoke task did not return expected output." >&2
    sed -n '1,120p' "${tmp_codex_basic}" >&2
    exit 1
fi
echo "Codex basic smoke task ok."

echo "Running Codex tool-calling smoke task..."
tool_smoke_failed=0
if ! (
    cd "${tmp_tool_workspace}" && timeout 240s codex exec -p "${CODEX_PROFILE_NAME}" --dangerously-bypass-approvals-and-sandbox "This is a harmless local smoke test in your own temporary workspace. Use bash exactly once and run: echo ${tool_marker} > ./codex-tools-marker.txt. Then reply with exactly one line: codex-ok:openrouter-tools"
) >"${tmp_codex_tools}" 2>&1; then
    tool_smoke_failed=1
fi

if [ "${tool_smoke_failed}" -eq 1 ]; then
    if [ "${CODEX_TOOL_SMOKE_MODE}" = "skip" ]; then
        echo "Skipping Codex tool-calling smoke task failure (CODEX_TOOL_SMOKE_MODE=skip)." >&2
    elif [ "${CODEX_TOOL_SMOKE_MODE}" = "auto" ] \
        && [ "${CODEX_PROFILE_NAME}" = "openrouter" ] \
        && grep -q "invalid_prompt" "${tmp_codex_tools}" \
        && grep -q "Invalid Responses API request" "${tmp_codex_tools}"; then
        echo "Warning: OpenRouter rejected Codex tool-call response payload (known compatibility issue)." >&2
        echo "Continuing because CODEX_TOOL_SMOKE_MODE=auto." >&2
    else
        echo "Error: Codex tool-calling smoke task failed." >&2
        sed -n '1,160p' "${tmp_codex_tools}" >&2
        exit 1
    fi
else
    if ! grep -q "codex-ok:openrouter-tools" "${tmp_codex_tools}"; then
        echo "Error: Codex tool-calling smoke task did not return expected output." >&2
        sed -n '1,160p' "${tmp_codex_tools}" >&2
        exit 1
    fi
    actual_marker="$(tr -d '\r\n' < "${tmp_tool_marker_file}" 2>/dev/null || true)"
    if [ "${actual_marker}" != "${tool_marker}" ]; then
        echo "Error: Codex tool-calling smoke task did not produce expected marker file content." >&2
        exit 1
    fi
    echo "Codex tool-calling smoke task ok."
fi

echo "Running Claude Code basic smoke task via OpenRouter..."
if ! timeout 180s claude -p "Reply with exactly one line: claude-ok:openrouter-basic" >"${tmp_claude_basic}" 2>&1; then
    echo "Error: Claude basic smoke task failed." >&2
    sed -n '1,120p' "${tmp_claude_basic}" >&2
    exit 1
fi
if ! grep -q "claude-ok:openrouter-basic" "${tmp_claude_basic}"; then
    echo "Error: Claude basic smoke task did not return expected output." >&2
    sed -n '1,120p' "${tmp_claude_basic}" >&2
    exit 1
fi
echo "Claude basic smoke task ok."

echo "Running Claude Code tool-calling smoke task via OpenRouter..."
if ! printf '%s\n' \
    "Use Bash exactly once and run: echo claude-openrouter-tools-marker >/dev/null. Then reply with exactly one line: claude-ok:openrouter-tools" \
    | timeout 240s claude -p \
        --no-session-persistence \
        --disable-slash-commands \
        --verbose \
        --output-format stream-json \
        --allowedTools Bash \
        --add-dir "${ROOT_DIR}" >"${tmp_claude_tools}" 2>&1; then
    echo "Error: Claude tool-calling smoke task failed." >&2
    sed -n '1,180p' "${tmp_claude_tools}" >&2
    exit 1
fi
if ! grep -q '"type":"tool_use"' "${tmp_claude_tools}" \
    || ! grep -q '"name":"Bash"' "${tmp_claude_tools}"; then
    echo "Error: Claude tool-calling smoke task did not invoke Bash tool." >&2
    sed -n '1,180p' "${tmp_claude_tools}" >&2
    exit 1
fi
if ! grep -q "\"permissionMode\":\"${CLAUDE_PERMISSION_MODE}\"" "${tmp_claude_tools}"; then
    echo "Error: Claude Code did not start with permission mode '${CLAUDE_PERMISSION_MODE}'." >&2
    sed -n '1,180p' "${tmp_claude_tools}" >&2
    exit 1
fi

claude_tool_result_line="$(awk '/"type":"result"/{print; exit}' "${tmp_claude_tools}" || true)"
claude_tool_result="$(printf '%s' "${claude_tool_result_line}" | jq -r '.result // empty' 2>/dev/null || true)"
case "${claude_tool_result}" in
    claude-ok:openrouter-tools*)
        ;;
    *)
        echo "Error: Claude tool-calling smoke task returned unexpected result." >&2
        sed -n '1,180p' "${tmp_claude_tools}" >&2
        exit 1
        ;;
esac
echo "Claude tool-calling smoke task ok."

echo "All GH/Codex/Claude verification checks passed."
