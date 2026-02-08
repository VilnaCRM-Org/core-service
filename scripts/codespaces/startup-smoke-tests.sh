#!/usr/bin/env bash
set -euo pipefail

ORG="${1:-${CODESPACE_GITHUB_ORG:-VilnaCRM-Org}}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=scripts/codespaces/lib/github-auth.sh
. "${SCRIPT_DIR}/lib/github-auth.sh"

cs_require_command gh
cs_require_command codex

echo "Running startup smoke tests..."

echo "Checking GitHub authentication..."
cs_ensure_gh_auth

echo "Checking repository listing for org '${ORG}'..."
repo_count="$(gh repo list "${ORG}" --limit 1 --json name --jq 'length')"
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

echo "Ensuring OpenRouter compatibility shim is running..."
bash "${SCRIPT_DIR}/start-openrouter-shim.sh"

tmp_last_msg="$(mktemp)"
tmp_captured_output="$(mktemp)"
cleanup() {
    rm -f "${tmp_last_msg}" "${tmp_captured_output}"
}
trap cleanup EXIT

echo "Checking Codex autonomous tool execution readiness..."
if ! codex exec \
    -p openrouter \
    --dangerously-bypass-approvals-and-sandbox \
    --output-last-message "${tmp_last_msg}" \
    "Run one shell command: pwd. Then reply with exactly one line: codex-startup-ok" \
    >"${tmp_captured_output}" 2>&1; then
    echo "Error: Codex startup smoke test failed." >&2
    sed -n '1,120p' "${tmp_captured_output}" >&2
    exit 1
fi

if ! grep -q '^codex-startup-ok$' "${tmp_last_msg}"; then
    echo "Error: Codex startup smoke test returned unexpected output." >&2
    cat "${tmp_last_msg}" >&2
    exit 1
fi

echo "Codex startup smoke test passed."
echo "Startup smoke tests completed successfully."
