#!/usr/bin/env bash
set -euo pipefail

ORG="${1:-VilnaCRM-Org}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=scripts/codespaces/lib/github-auth.sh
. "${SCRIPT_DIR}/lib/github-auth.sh"

cs_require_command gh
cs_require_command codex

echo "Checking GitHub authentication..."
cs_ensure_gh_auth

echo "Checking GitHub token scopes (if available)..."
scopes="$(
    gh api -i /user 2>/dev/null \
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
repo_count="$(gh repo list "${ORG}" --limit 1000 --json name --jq 'length')"
if [ "${repo_count}" -le 0 ]; then
    echo "Error: failed to list repositories for org '${ORG}'." >&2
    exit 1
fi
echo "Repository listing ok (${repo_count} repositories visible)."

echo "Checking current PR CI status..."
pr_number="$(gh pr view --json number --jq '.number' 2>/dev/null || true)"
if [ -n "${pr_number}" ]; then
    if ! gh pr checks "${pr_number}" --json name,state >/dev/null; then
        cat >&2 <<EOM
Error: failed to query checks for PR #${pr_number}.
Ensure your authentication can read pull request checks/actions metadata for this repository.
EOM
        exit 1
    fi
    non_success_count="$(
        gh pr checks "${pr_number}" --json state \
            --jq '[.[].state | select(. != "SUCCESS" and . != "SKIPPED" and . != "NEUTRAL")] | length'
    )"
    echo "PR #${pr_number} checks query ok (non-success states: ${non_success_count})."
else
    echo "No PR detected for current branch. Skipping PR checks."
fi

echo "Checking git push permissions on current branch..."
current_branch="$(git rev-parse --abbrev-ref HEAD)"
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

echo "Running codex smoke task via OpenRouter profile..."
tmp_last_msg="$(mktemp)"
tmp_captured_output="$(mktemp)"
cleanup() {
    rm -f "${tmp_last_msg}" "${tmp_captured_output}"
}
trap cleanup EXIT

if ! codex exec \
    -p openrouter \
    --sandbox read-only \
    --output-last-message "${tmp_last_msg}" \
    "Inspect this repository and respond with exactly one line in this format: codex-ok:<short-summary>" \
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

echo "Codex smoke task ok: $(cat "${tmp_last_msg}")"
echo "All GH/Codex verification checks passed."
