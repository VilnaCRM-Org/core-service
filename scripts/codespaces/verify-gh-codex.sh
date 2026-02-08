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

cs_require_command gh
cs_require_command codex

echo "Checking GitHub authentication..."
cs_ensure_gh_auth

echo "Checking GitHub token scopes (if available)..."
scopes_headers="$(gh api -i /user 2>/dev/null || true)"
scopes="$(
    printf '%s' "${scopes_headers}" \
        | tr -d '\r' \
        | awk -F': ' 'tolower($1)=="x-oauth-scopes"{print $2; exit}'
)"

if [ -n "$scopes" ]; then
    echo "Available token scopes: $scopes"
    normalized_scopes="$(echo "$scopes" | tr -d ' ')"
    for required_scope in repo read:org; do
        if [[ ",$normalized_scopes," != *",$required_scope,"* ]]; then
            echo "Warning: expected scope '$required_scope' is missing." >&2
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
    non_success_count="$(
        printf '%s' "${checks_json}" \
            | jq '[.[].state | select(. != "SUCCESS" and . != "SKIPPED" and . != "NEUTRAL")] | length'
    )"
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
Provide OPENROUTER_API_KEY to verify Codex OpenRouter execution.
EOM
    exit 1
fi

echo "Checking codex login status..."
if ! codex login status >/dev/null 2>&1; then
    echo "Note: codex login is not configured. Continuing because OpenRouter API key auth is used." >&2
fi

echo "Running codex smoke task via OpenRouter profile..."
tmp_last_msg=""
tmp_captured_output=""
tmp_tool_last_msg=""
tmp_tool_captured_output=""
tmp_tool_workspace=""
cleanup() {
    [ -n "${tmp_last_msg}" ] && rm -f "${tmp_last_msg}"
    [ -n "${tmp_captured_output}" ] && rm -f "${tmp_captured_output}"
    [ -n "${tmp_tool_last_msg}" ] && rm -f "${tmp_tool_last_msg}"
    [ -n "${tmp_tool_captured_output}" ] && rm -f "${tmp_tool_captured_output}"
    [ -n "${tmp_tool_workspace}" ] && rm -rf "${tmp_tool_workspace}"
}
trap cleanup EXIT

tmp_last_msg="$(mktemp)"
tmp_captured_output="$(mktemp)"
tmp_tool_last_msg="$(mktemp)"
tmp_tool_captured_output="$(mktemp)"
tmp_tool_workspace="$(mktemp -d)"

# Prompt-only smoke test validates OpenRouter connectivity for Codex.
if ! codex exec \
    -p openrouter \
    --sandbox read-only \
    --output-last-message "${tmp_last_msg}" \
    "Reply with exactly one line: codex-ok:openrouter-basic" \
    >"${tmp_captured_output}" 2>&1; then
    echo "Error: codex smoke task execution failed." >&2
    echo "Codex output:" >&2
    sed -n '1,80p' "${tmp_captured_output}" >&2
    exit 1
fi

if ! grep -q '^codex-ok:' "${tmp_last_msg}"; then
    echo "Error: codex smoke task did not return expected output." >&2
    echo "Last message:" >&2
    cat "${tmp_last_msg}" >&2
    exit 1
fi

echo "Codex basic smoke task ok: $(cat "${tmp_last_msg}")"
tool_profile="openrouter"
echo "Running Codex tool-calling smoke task via profile '${tool_profile}' (full access, no approvals)..."

# Tool-calling smoke test validates autonomous coding capability.
# Full-access mode is intentional for this check. Mitigations: disposable workspace,
# timeout, deterministic marker validation, and captured output logs.
if ! (
    cd "${tmp_tool_workspace}" && timeout 120s codex exec \
        -p "${tool_profile}" \
        --dangerously-bypass-approvals-and-sandbox \
        --output-last-message "${tmp_tool_last_msg}" \
        "Use the shell tool exactly once and run: true. Then reply with exactly one line: codex-ok:${tool_profile}-tools"
) >"${tmp_tool_captured_output}" 2>&1; then
    if grep -q "ZodError" "${tmp_tool_captured_output}"; then
        cat >&2 <<'EOM'
Error: OpenRouter rejected Codex tool-calling payloads.
Result: prompt-only Codex works, but autonomous coding actions (edit/refactor/test/commit flows) are blocked.
Current profile already uses full access and no approvals:
  - model: openai/gpt-5.2-codex
  - provider: OpenRouter
  - reasoning: xhigh
Ensure profile also sets:
  - model_reasoning_summary = "none"
Check Codex provider configuration:
  - ~/.codex/config.toml
EOM
    else
        echo "Error: codex tool-calling smoke task failed." >&2
    fi
    echo "Codex output:" >&2
    sed -n '1,120p' "${tmp_tool_captured_output}" >&2
    exit 1
fi

if ! grep -q '^codex-ok:' "${tmp_tool_last_msg}"; then
    echo "Error: codex tool-calling smoke task did not return expected output." >&2
    echo "Last message:" >&2
    cat "${tmp_tool_last_msg}" >&2
    exit 1
fi

echo "Codex tool-calling smoke task ok: $(cat "${tmp_tool_last_msg}")"
echo "All GH/Codex verification checks passed."
