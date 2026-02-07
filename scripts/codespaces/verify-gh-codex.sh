#!/usr/bin/env bash
set -euo pipefail

ORG="${1:-VilnaCRM-Org}"

if ! command -v gh >/dev/null 2>&1; then
    echo "Error: gh CLI is required." >&2
    exit 1
fi

if ! command -v codex >/dev/null 2>&1; then
    echo "Error: codex CLI is required." >&2
    exit 1
fi

echo "Checking GitHub authentication..."
gh auth status >/dev/null

echo "Checking GitHub token scopes..."
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
    if [[ ",$normalized_scopes," != *",workflow,"* ]]; then
        echo "Note: 'workflow' scope is not present. It may be required in some org policies to query Actions metadata." >&2
    fi
else
    echo "Warning: could not read scope header (this can happen with some token types)." >&2
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
        cat >&2 <<EOF
Error: failed to query checks for PR #${pr_number}.
Ensure your token can read pull request checks/actions metadata for this repository.
EOF
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

echo "Checking codex login..."
codex login status >/dev/null 2>&1

echo "Running codex smoke task (read-only)..."
tmp_last_msg="$(mktemp)"
cleanup() {
    rm -f "${tmp_last_msg}"
}
trap cleanup EXIT

if ! codex exec \
    --sandbox read-only \
    --output-last-message "${tmp_last_msg}" \
    "Inspect this repository and respond with exactly one line in this format: codex-ok:<short-summary>" \
    >/dev/null 2>&1; then
    echo "Error: codex smoke task execution failed." >&2
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
