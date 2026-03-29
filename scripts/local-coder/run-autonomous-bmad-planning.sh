#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../.." && pwd)"
# shellcheck source=scripts/local-coder/lib/workspace-secrets.sh
. "${ROOT_DIR}/scripts/local-coder/lib/workspace-secrets.sh"
# shellcheck source=scripts/local-coder/lib/autonomous-bmad-planning.sh
. "${ROOT_DIR}/scripts/local-coder/lib/autonomous-bmad-planning.sh"

SETTINGS_FILE="${ROOT_DIR}/.devcontainer/workspace-settings.env"
if [ -f "${SETTINGS_FILE}" ]; then
    # shellcheck disable=SC1090
    . "${SETTINGS_FILE}"
fi

if [ -f "${HOME}/.config/core-service/agent-secrets.env" ]; then
    # shellcheck disable=SC1091
    . "${HOME}/.config/core-service/agent-secrets.env"
fi
if [ -f "${HOME}/.config/openclaw/agent-secrets.env" ]; then
    # shellcheck disable=SC1091
    . "${HOME}/.config/openclaw/agent-secrets.env"
fi

cs_load_host_workspace_secrets

usage() {
    cat <<'EOM'
Usage: bash scripts/local-coder/run-autonomous-bmad-planning.sh --task <description> [options]

Launch a fresh non-interactive Codex session that reads the repository's
autonomous BMAD planning skill and produces a specs-only planning bundle.

Options:
  --task <description>         Short task description to plan (required)
  --bundle-id <id>             Stable bundle id. Defaults to timestamp + slug
  --max-validation-rounds <n>  Validation rounds per artifact (1-3, default: 3)
  --issue-mode <skip|create>   GitHub issue behavior (default: skip)
  --pr-mode <skip|draft>       GitHub PR behavior (default: skip)
  --repo <owner/name>          GitHub repository slug (default: origin remote)
  --base-branch <branch>       Base branch for specs-only PRs (default: main)
  --model <model>              Optional Codex model override
  --result-file <path>         Write final JSON result to this file
  --dry-run                    Print the resolved run plan without launching Codex
  -h, --help                   Show this help text

Examples:
  bash scripts/local-coder/run-autonomous-bmad-planning.sh \
    --task "Plan an audit trail feature for customer updates"

  bash scripts/local-coder/run-autonomous-bmad-planning.sh \
    --task "Plan API-level customer tagging" \
    --max-validation-rounds 2 \
    --issue-mode create
EOM
}

task=""
bundle_id=""
max_validation_rounds=3
issue_mode="skip"
pr_mode="skip"
repo_slug=""
base_branch="main"
model=""
result_file=""
dry_run=false
seed_paths=()

while [ $# -gt 0 ]; do
    case "$1" in
        --task)
            task="${2:?Missing value for --task}"
            shift 2
            ;;
        --bundle-id)
            bundle_id="${2:?Missing value for --bundle-id}"
            shift 2
            ;;
        --max-validation-rounds)
            max_validation_rounds="${2:?Missing value for --max-validation-rounds}"
            shift 2
            ;;
        --issue-mode)
            issue_mode="${2:?Missing value for --issue-mode}"
            shift 2
            ;;
        --pr-mode)
            pr_mode="${2:?Missing value for --pr-mode}"
            shift 2
            ;;
        --repo)
            repo_slug="${2:?Missing value for --repo}"
            shift 2
            ;;
        --base-branch)
            base_branch="${2:?Missing value for --base-branch}"
            shift 2
            ;;
        --model)
            model="${2:?Missing value for --model}"
            shift 2
            ;;
        --result-file)
            result_file="${2:?Missing value for --result-file}"
            shift 2
            ;;
        --dry-run)
            dry_run=true
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            echo "Error: unknown argument '$1'." >&2
            usage >&2
            exit 1
            ;;
    esac
done

if [ -z "${task}" ]; then
    echo "Error: --task is required." >&2
    usage >&2
    exit 1
fi

cs_abp_validate_rounds "${max_validation_rounds}"

case "${issue_mode}" in
    skip|create) ;;
    *)
        echo "Error: --issue-mode must be 'skip' or 'create'." >&2
        exit 1
        ;;
esac

case "${pr_mode}" in
    skip|draft) ;;
    *)
        echo "Error: --pr-mode must be 'skip' or 'draft'." >&2
        exit 1
        ;;
esac

config_file="$(cs_abp_resolve_config_file "${ROOT_DIR}")"
planning_artifacts="$(cs_abp_read_config_value "${config_file}" "planning_artifacts")"
planning_artifacts_dir="$(cs_abp_absolutize_path "${ROOT_DIR}" "${planning_artifacts}")"

if [ -z "${repo_slug}" ]; then
    repo_slug="$(cs_abp_default_repo_slug "${ROOT_DIR}")"
fi

if [ -z "${bundle_id}" ]; then
    bundle_id="$(date -u +%Y%m%d-%H%M%S)-$(cs_abp_slugify "${task}")"
fi

bundle_dir="${planning_artifacts_dir%/}/autonomous/${bundle_id}"
schema_path="${ROOT_DIR}/scripts/local-coder/schemas/autonomous-bmad-planning-result.schema.json"
skill_path="${ROOT_DIR}/.claude/skills/bmad-autonomous-planning/SKILL.md"
wrapper_path="${ROOT_DIR}/.agents/skills/bmad-autonomous-planning/SKILL.md"

if [ ! -f "${schema_path}" ]; then
    echo "Error: result schema not found at '${schema_path}'." >&2
    exit 1
fi

if [ ! -f "${skill_path}" ]; then
    echo "Error: skill file not found at '${skill_path}'." >&2
    exit 1
fi

if [ ! -f "${wrapper_path}" ]; then
    echo "Error: wrapper skill file not found at '${wrapper_path}'." >&2
    exit 1
fi

if { [ "${issue_mode}" = "create" ] || [ "${pr_mode}" = "draft" ]; } && [ -z "${repo_slug}" ]; then
    echo "Error: unable to infer repository slug from origin remote. Pass --repo explicitly." >&2
    exit 1
fi

if [ -d "${bundle_dir}" ] && find "${bundle_dir}" -mindepth 1 -maxdepth 1 | read -r _; then
    echo "Error: bundle directory '${bundle_dir}' already exists and is not empty. Pass a new --bundle-id." >&2
    exit 1
fi

if [ -n "${result_file}" ]; then
    result_file="$(cs_abp_absolutize_path "${ROOT_DIR}" "${result_file}")"
fi

mapfile -t seed_paths < <(cs_abp_seed_paths_from_task "${ROOT_DIR}" "${task}")

if [ "${dry_run}" = true ]; then
    printf 'Dry run only.\n'
    printf 'Task: %s\n' "${task}"
    printf 'Bundle ID: %s\n' "${bundle_id}"
    printf 'Bundle directory: %s\n' "${bundle_dir}"
    printf 'Skill: %s\n' "${skill_path}"
    printf 'Wrapper: %s\n' "${wrapper_path}"
    printf 'Schema: %s\n' "${schema_path}"
    printf 'Issue mode: %s\n' "${issue_mode}"
    printf 'PR mode: %s\n' "${pr_mode}"
    printf 'Repo: %s\n' "${repo_slug:-<none>}"
    printf 'Base branch: %s\n' "${base_branch}"
    printf 'Validation rounds: %s\n' "${max_validation_rounds}"
    if [ "${#seed_paths[@]}" -gt 0 ]; then
        printf 'Seed paths:\n'
        printf '  - %s\n' "${seed_paths[@]}"
    fi
    exit 0
fi

mkdir -p "${bundle_dir}/validation"

if ! command -v codex >/dev/null 2>&1; then
    echo "Error: Codex CLI is not installed or not in PATH." >&2
    exit 1
fi

if codex login status >/dev/null 2>&1; then
    unset OPENAI_API_KEY
elif [ -z "${OPENAI_API_KEY:-}" ]; then
    cat >&2 <<'EOM'
Error: Codex authentication is not configured.
Provide OPENAI_API_KEY in your workspace secrets or run:
  codex login
EOM
    exit 1
fi

tmp_prompt_file=""
tmp_codex_output=""
tmp_last_message=""
cleanup() {
    [ -n "${tmp_prompt_file}" ] && rm -f "${tmp_prompt_file}"
    [ -n "${tmp_codex_output}" ] && rm -f "${tmp_codex_output}"
    [ -n "${tmp_last_message}" ] && rm -f "${tmp_last_message}"
}
trap cleanup EXIT

tmp_prompt_file="$(mktemp)"
tmp_codex_output="$(mktemp)"
tmp_last_message="$(mktemp)"

cat >"${tmp_prompt_file}" <<EOM
Read and execute the repository skill at:
- ${wrapper_path}
- ${skill_path}

You are running a fully autonomous BMAD planning session. Do not pause for human confirmation.

Task description:
${task}

Run configuration:
- bundle_id: ${bundle_id}
- bundle_dir: ${bundle_dir}
- max_validation_rounds: ${max_validation_rounds}
- issue_mode: ${issue_mode}
- pr_mode: ${pr_mode}
- repo_slug: ${repo_slug}
- base_branch: ${base_branch}

$(if [ "${#seed_paths[@]}" -gt 0 ]; then
    printf 'Initial repository paths to inspect first:\n'
    printf -- '- %s\n' "${seed_paths[@]}"
    printf '\nStart with these paths. Do not expand to other repository areas until they prove insufficient.\n'
fi)

Hard requirements:
- generate specs only; do not implement production code
- keep file writes scoped to ${bundle_dir} unless GitHub issue or PR creation is explicitly requested
- use the existing _bmad workflows and repository docs as process guidance, but do not let interactive menus block progress
- keep context intentionally small; do not bulk-scan the entire repository
- do not read .claude/skills/AI-AGENT-GUIDE.md or .claude/skills/SKILL-DECISION-GUIDE.md in this child run; the launcher already selected the correct skill
- do not reopen general repository meta-guides unless the planning skill explicitly needs them to unblock execution
- read the named BMAD workflow files directly instead of broad searches through _bmad whenever possible
- inspect only the minimum relevant docs and code paths needed to justify the plan
- infer the likely feature area from the task description and start with the 1-3 most likely paths
- stop discovery once you have enough evidence to write the bundle; avoid marginal context gathering
- when a workflow would normally halt for input, decide internally and continue
- answer subagent questions yourself from repository context whenever possible
- validate research, brief, PRD, architecture, epics, and stories for between 1 and ${max_validation_rounds} rounds
- create ${bundle_dir}/run-summary.md with the reasoning trace, validation round counts, warnings, and open questions
- if GitHub side effects fail, still finish the planning run and report the failure in the final JSON
- if GitHub shell commands are needed, use a login shell (for example bash -l -c) so workspace credentials are loaded

Return JSON only and make sure it matches the provided output schema exactly.
EOM

codex_cmd=(
    timeout 3600s
    codex exec
    --json
    --output-schema "${schema_path}"
    --output-last-message "${tmp_last_message}"
    --dangerously-bypass-approvals-and-sandbox
    -C "${ROOT_DIR}"
)

if [ -n "${model}" ]; then
    codex_cmd+=(--model "${model}")
fi

codex_cmd+=(-)

if ! "${codex_cmd[@]}" <"${tmp_prompt_file}" >"${tmp_codex_output}" 2>&1; then
    echo "Error: autonomous BMAD planning run failed." >&2
    sed -n '1,160p' "${tmp_codex_output}" >&2
    exit 1
fi

if [ ! -s "${tmp_last_message}" ]; then
    echo "Error: Codex did not produce a final JSON message." >&2
    sed -n '1,160p' "${tmp_codex_output}" >&2
    exit 1
fi

if [ -n "${result_file}" ]; then
    mkdir -p "$(dirname "${result_file}")"
    cp "${tmp_last_message}" "${result_file}"
fi

cat "${tmp_last_message}"
