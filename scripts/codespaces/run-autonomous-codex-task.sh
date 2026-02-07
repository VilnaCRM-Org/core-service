#!/usr/bin/env bash
set -euo pipefail

if [ $# -lt 1 ]; then
    cat >&2 <<'EOF'
Usage:
  scripts/codespaces/run-autonomous-codex-task.sh "Your implementation task prompt"
EOF
    exit 1
fi

TASK_PROMPT="$1"
WORK_DIR="${WORK_DIR:-$(pwd)}"

if ! command -v codex >/dev/null 2>&1; then
    echo "Error: codex CLI is required." >&2
    exit 1
fi

if ! command -v gh >/dev/null 2>&1; then
    echo "Error: gh CLI is required." >&2
    exit 1
fi

cd "${WORK_DIR}"

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    echo "Error: must run inside a git repository." >&2
    exit 1
fi

current_branch="$(git rev-parse --abbrev-ref HEAD)"
if [ "${current_branch}" = "main" ] || [ "${current_branch}" = "master" ]; then
    echo "Error: refusing to run autonomous flow directly on ${current_branch}." >&2
    exit 1
fi

if [ -z "${ALLOW_DIRTY:-}" ] && [ -n "$(git status --porcelain)" ]; then
    echo "Error: working tree is dirty. Commit/stash first or set ALLOW_DIRTY=1." >&2
    exit 1
fi

if ! codex login status >/dev/null 2>&1; then
    echo "Error: codex is not logged in." >&2
    exit 1
fi

gh auth status >/dev/null

echo "Running autonomous codex task on branch '${current_branch}'..."

codex exec \
    --full-auto \
    --sandbox workspace-write \
    --cd "${WORK_DIR}" \
    "You are operating in ${WORK_DIR} on branch ${current_branch}.
Implement the following task fully:
${TASK_PROMPT}

Requirements:
- Follow repository instructions (including Make-based workflow).
- Run relevant tests and then run: make ci
- If CI passes, commit with a clear message and push branch updates.
- Provide a concise final summary with changed files and test/CI results." 

echo "Autonomous codex task finished."
