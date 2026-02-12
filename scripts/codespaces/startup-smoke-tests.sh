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

: "${CODEX_PROFILE_NAME:=openrouter}"
: "${CLAUDE_DEFAULT_MODEL:=anthropic/claude-sonnet-4.5}"
: "${CLAUDE_PERMISSION_MODE:=bypassPermissions}"

cs_require_command gh
cs_require_command jq
cs_require_command codex
cs_require_command claude
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

echo "Checking Codex profile configuration..."
if [ ! -f "${HOME}/.codex/config.toml" ]; then
    echo "Error: Codex config file is missing: ${HOME}/.codex/config.toml" >&2
    exit 1
fi
if ! grep -q "profile = \"${CODEX_PROFILE_NAME}\"" "${HOME}/.codex/config.toml"; then
    echo "Error: Codex default profile '${CODEX_PROFILE_NAME}' is not configured." >&2
    exit 1
fi

echo "Running Codex smoke task via OpenRouter profile..."
tmp_codex_output=""
tmp_claude_output=""
tmp_claude_tools_output=""
cleanup() {
    [ -n "${tmp_codex_output}" ] && rm -f "${tmp_codex_output}"
    [ -n "${tmp_claude_output}" ] && rm -f "${tmp_claude_output}"
    [ -n "${tmp_claude_tools_output}" ] && rm -f "${tmp_claude_tools_output}"
}
trap cleanup EXIT
tmp_codex_output="$(mktemp)"

if ! timeout 180s codex exec -p "${CODEX_PROFILE_NAME}" --dangerously-bypass-approvals-and-sandbox "Reply with exactly one line: codex-startup-ok" >"${tmp_codex_output}" 2>&1; then
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

echo "Checking Claude Code default model config..."
if [ ! -f "${HOME}/.claude/settings.json" ]; then
    echo "Error: Claude settings file is missing: ${HOME}/.claude/settings.json" >&2
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

echo "Running Claude Code smoke task..."
tmp_claude_output="$(mktemp)"
if ! timeout 180s claude -p "Reply with exactly one line: claude-startup-ok" >"${tmp_claude_output}" 2>&1; then
    echo "Error: Claude Code smoke task failed." >&2
    sed -n '1,120p' "${tmp_claude_output}" >&2
    exit 1
fi

if ! grep -q "claude-startup-ok" "${tmp_claude_output}"; then
    echo "Error: Claude Code smoke task did not return expected output." >&2
    sed -n '1,120p' "${tmp_claude_output}" >&2
    exit 1
fi

echo "Claude Code startup smoke test passed."

echo "Running Claude Code tool-calling smoke task..."
tmp_claude_tools_output="$(mktemp)"
if ! printf '%s\n' \
    "Use Bash exactly once and run: echo claude-startup-tools-marker >/dev/null. Then reply with exactly one line: claude-startup-tools-ok" \
    | timeout 240s claude -p \
        --no-session-persistence \
        --disable-slash-commands \
        --verbose \
        --output-format stream-json \
        --allowedTools Bash \
        --add-dir "${ROOT_DIR}" >"${tmp_claude_tools_output}" 2>&1; then
    echo "Error: Claude Code tool-calling smoke task failed." >&2
    sed -n '1,160p' "${tmp_claude_tools_output}" >&2
    exit 1
fi

if ! grep -q '"type":"tool_use"' "${tmp_claude_tools_output}" \
    || ! grep -q '"name":"Bash"' "${tmp_claude_tools_output}"; then
    echo "Error: Claude Code tool-calling smoke task did not invoke Bash tool." >&2
    sed -n '1,160p' "${tmp_claude_tools_output}" >&2
    exit 1
fi
if ! grep -q "\"permissionMode\":\"${CLAUDE_PERMISSION_MODE}\"" "${tmp_claude_tools_output}"; then
    echo "Error: Claude Code did not start with permission mode '${CLAUDE_PERMISSION_MODE}'." >&2
    sed -n '1,120p' "${tmp_claude_tools_output}" >&2
    exit 1
fi

claude_tool_result_line="$(awk '/"type":"result"/{print; exit}' "${tmp_claude_tools_output}" || true)"
claude_tool_result="$(printf '%s' "${claude_tool_result_line}" | jq -r '.result // empty' 2>/dev/null || true)"
case "${claude_tool_result}" in
    claude-startup-tools-ok*)
        ;;
    *)
        echo "Error: Claude Code tool-calling smoke task returned unexpected result." >&2
        sed -n '1,160p' "${tmp_claude_tools_output}" >&2
        exit 1
        ;;
esac

echo "Claude Code tool-calling smoke test passed."
echo "Startup smoke tests completed successfully."
