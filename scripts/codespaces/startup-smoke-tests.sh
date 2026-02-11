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

: "${CODEX_PROFILE_NAME:=openrouter}"
: "${CLAUDE_DEFAULT_MODEL:=anthropic/claude-sonnet-4.5}"

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

if [ -f "${HOME}/.config/core-service/agent-secrets.env" ]; then
    # shellcheck disable=SC1091
    . "${HOME}/.config/core-service/agent-secrets.env"
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
tmp_codex_output="$(mktemp)"
cleanup() {
    rm -f "${tmp_codex_output}"
}
trap cleanup EXIT

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

echo "Running Claude Code smoke task..."
tmp_claude_output="$(mktemp)"
trap 'cleanup; rm -f "${tmp_claude_output}"' EXIT
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
echo "Startup smoke tests completed successfully."
