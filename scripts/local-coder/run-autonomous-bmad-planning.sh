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
BMALPH wrapper instructions plus the autonomous planning skill and produces a
specs-only planning bundle.

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
tmp_issue_body=""
tmp_pr_body=""
tmp_final_message=""
tmp_codex_home=""
tmp_pr_worktree=""

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
bmalph_commands_path="$(cs_abp_resolve_bmalph_commands_file "${ROOT_DIR}")"
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
    printf 'BMALPH commands: %s\n' "${bmalph_commands_path}"
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
    [ -n "${tmp_final_message}" ] && rm -f "${tmp_final_message}"
    [ -n "${tmp_issue_body}" ] && rm -f "${tmp_issue_body}"
    [ -n "${tmp_pr_body}" ] && rm -f "${tmp_pr_body}"
    [ -n "${tmp_pr_worktree}" ] && git -C "${ROOT_DIR}" worktree remove --force "${tmp_pr_worktree}" >/dev/null 2>&1 || true
    [ -n "${tmp_pr_worktree}" ] && rm -rf "${tmp_pr_worktree}"
    [ -n "${tmp_codex_home}" ] && rm -rf "${tmp_codex_home}"
}
trap cleanup EXIT

write_markdown_list() {
    local heading="${1:?Missing heading}"
    shift
    local item=""

    if [ "$#" -eq 0 ]; then
        return 0
    fi

    printf '## %s\n\n' "${heading}"
    for item in "$@"; do
        [ -n "${item}" ] && printf -- '- %s\n' "${item}"
    done
    printf '\n'
}

write_json_array_markdown_list() {
    local heading="${1:?Missing heading}"
    local json_file="${2:?Missing JSON file}"
    local jq_path="${3:?Missing jq path}"
    local -a items=()

    mapfile -t items < <(jq -r "${jq_path}[]?" "${json_file}")
    if [ "${#items[@]}" -eq 0 ]; then
        return 0
    fi

    write_markdown_list "${heading}" "${items[@]}"
}

write_github_issue_body() {
    local json_file="${1:?Missing JSON file}"
    local body_file="${2:?Missing body file}"
    local task_value=""
    local bundle_id_value=""
    local bundle_dir_value=""
    local run_summary_path=""
    local readiness_path=""

    task_value="$(jq -r '.task' "${json_file}")"
    bundle_id_value="$(jq -r '.bundle_id' "${json_file}")"
    bundle_dir_value="$(jq -r '.bundle_dir' "${json_file}")"
    run_summary_path="$(jq -r '.artifacts.run_summary' "${json_file}")"
    readiness_path="$(jq -r '.artifacts.implementation_readiness' "${json_file}")"

    {
        printf '## Task\n\n%s\n\n' "${task_value}"
        printf '## Planning Bundle\n\n'
        printf -- '- Bundle ID: `%s`\n' "${bundle_id_value}"
        printf -- '- Bundle directory: `%s`\n' "${bundle_dir_value}"
        printf -- '- Generated artifacts:\n'
        jq -r '.artifacts | to_entries[] | select(.value != null) | "  - `\(.key)`: `\(.value)`"' "${json_file}"
        printf '\n'

        printf '## Validation Rounds\n\n'
        jq -r '.validation_rounds | to_entries[] | "- `\(.key)`: \(.value)"' "${json_file}"
        printf '\n'

        write_json_array_markdown_list "Open Questions" "${json_file}" '.open_questions'
        write_json_array_markdown_list "Warnings" "${json_file}" '.warnings'

        if [ -f "${readiness_path}" ]; then
            printf '## Implementation Readiness\n\n'
            sed -n '1,200p' "${readiness_path}"
            printf '\n\n'
        fi

        if [ -f "${run_summary_path}" ]; then
            printf '## Run Summary\n\n'
            sed -n '1,200p' "${run_summary_path}"
            printf '\n'
        fi
    } >"${body_file}"
}

write_github_pr_body() {
    local json_file="${1:?Missing JSON file}"
    local body_file="${2:?Missing body file}"
    local issue_number_value="${3:-}"
    local bundle_id_value=""
    local task_value=""

    bundle_id_value="$(jq -r '.bundle_id' "${json_file}")"
    task_value="$(jq -r '.task' "${json_file}")"

    {
        printf '## Summary\n\n'
        printf 'Specs-only planning bundle for `%s`.\n\n' "${task_value}"
        if [ -n "${issue_number_value}" ]; then
            printf 'Refs #%s\n\n' "${issue_number_value}"
        fi

        printf '## Bundle\n\n'
        printf -- '- Bundle ID: `%s`\n' "${bundle_id_value}"
        printf -- '- Generated via `make bmalph-autonomous-plan`\n'
        printf -- '- Production code was intentionally not changed\n\n'

        printf '## Validation Rounds\n\n'
        jq -r '.validation_rounds | to_entries[] | "- `\(.key)`: \(.value)"' "${json_file}"
        printf '\n'

        write_json_array_markdown_list "Open Questions" "${json_file}" '.open_questions'
        write_json_array_markdown_list "Warnings" "${json_file}" '.warnings'
    } >"${body_file}"
}

create_github_issue() {
    local json_file="${1:?Missing JSON file}"
    local issue_title=""
    local issue_output=""

    tmp_issue_body="$(mktemp)"
    write_github_issue_body "${json_file}" "${tmp_issue_body}"
    issue_title="Planning bundle: $(jq -r '.task' "${json_file}")"

    if ! issue_output="$(
        bash -l -c 'gh issue create --repo "$1" --title "$2" --body-file "$3"' _ \
            "${repo_slug}" "${issue_title}" "${tmp_issue_body}" 2>&1
    )"; then
        printf '%s' "${issue_output}"
        return 1
    fi

    printf '%s\n' "${issue_output}" | tail -n 1
}

create_specs_pr() {
    local json_file="${1:?Missing JSON file}"
    local issue_number_value="${2:-}"
    local bundle_dir_value=""
    local bundle_rel_path=""
    local bundle_id_value=""
    local branch_name=""
    local base_ref=""
    local pr_title=""
    local pr_output=""

    bundle_dir_value="$(jq -r '.bundle_dir' "${json_file}")"
    bundle_id_value="$(jq -r '.bundle_id' "${json_file}")"

    case "${bundle_dir_value}" in
        "${ROOT_DIR}"/*)
            bundle_rel_path="${bundle_dir_value#${ROOT_DIR}/}"
            ;;
        *)
            echo "Bundle directory '${bundle_dir_value}' is outside the repository root, so a specs-only PR cannot be created."
            return 1
            ;;
    esac

    if [ ! -d "${bundle_dir_value}" ]; then
        echo "Bundle directory '${bundle_dir_value}' does not exist."
        return 1
    fi

    branch_name="specs/${bundle_id_value}"
    if git -C "${ROOT_DIR}" show-ref --verify --quiet "refs/heads/${branch_name}" || \
        git -C "${ROOT_DIR}" ls-remote --exit-code --heads origin "${branch_name}" >/dev/null 2>&1; then
        echo "Branch '${branch_name}' already exists. Use a different bundle id before requesting a specs-only PR."
        return 1
    fi

    base_ref="${base_branch}"
    if git -C "${ROOT_DIR}" show-ref --verify --quiet "refs/remotes/origin/${base_branch}"; then
        base_ref="origin/${base_branch}"
    fi

    tmp_pr_worktree="$(mktemp -d)"
    if ! git -C "${ROOT_DIR}" worktree add -b "${branch_name}" "${tmp_pr_worktree}" "${base_ref}" >/dev/null 2>&1; then
        echo "Unable to create a temporary worktree for branch '${branch_name}' from '${base_ref}'."
        return 1
    fi

    rm -rf "${tmp_pr_worktree:?}/${bundle_rel_path}"
    mkdir -p "$(dirname "${tmp_pr_worktree}/${bundle_rel_path}")"
    cp -R "${bundle_dir_value}" "${tmp_pr_worktree}/${bundle_rel_path}"

    git -C "${tmp_pr_worktree}" config user.name "${GIT_AUTHOR_NAME:-Codex}"
    git -C "${tmp_pr_worktree}" config user.email "${GIT_AUTHOR_EMAIL:-codex@local}"
    git -C "${tmp_pr_worktree}" add "${bundle_rel_path}"

    if git -C "${tmp_pr_worktree}" diff --cached --quiet; then
        echo "No specs changes were staged for '${bundle_rel_path}'."
        return 1
    fi

    if ! git -C "${tmp_pr_worktree}" commit -m "docs: add planning bundle ${bundle_id_value}" >/dev/null 2>&1; then
        echo "Unable to commit specs bundle '${bundle_rel_path}'."
        return 1
    fi

    if ! git -C "${tmp_pr_worktree}" push -u origin "${branch_name}" >/dev/null 2>&1; then
        echo "Unable to push specs branch '${branch_name}' to origin."
        return 1
    fi

    tmp_pr_body="$(mktemp)"
    write_github_pr_body "${json_file}" "${tmp_pr_body}" "${issue_number_value}"
    pr_title="docs: add planning bundle ${bundle_id_value}"

    if ! pr_output="$(
        bash -l -c 'gh pr create --repo "$1" --draft --base "$2" --head "$3" --title "$4" --body-file "$5"' _ \
            "${repo_slug}" "${base_branch}" "${branch_name}" "${pr_title}" "${tmp_pr_body}" 2>&1
    )"; then
        printf '%s' "${pr_output}"
        return 1
    fi

    printf '%s\n' "${pr_output}" | tail -n 1
}

finalize_result_json() {
    local source_file="${1:?Missing source JSON file}"
    local destination_file="${2:?Missing destination JSON file}"
    local issue_status_value="${3:?Missing issue status}"
    local issue_number_value="${4:?Missing issue number value}"
    local issue_url_value="${5-}"
    local pr_status_value="${6:?Missing PR status}"
    local pr_number_value="${7:?Missing PR number value}"
    local pr_url_value="${8-}"
    local branch_value="${9-}"
    local error_value="${10-}"
    local warning_value=""

    warning_value="$(
        jq -n -r \
            --arg issue_mode_value "${issue_mode}" \
            --arg issue_status_value "${issue_status_value}" \
            --arg pr_mode_value "${pr_mode}" \
            --arg pr_status_value "${pr_status_value}" \
            --arg branch_value "${branch_value}" \
            '
            [
              (if $issue_mode_value == "create" and $issue_status_value == "created" then "GitHub issue created by the trusted launcher after the Codex planning run." else empty end),
              (if $pr_mode_value == "draft" and $pr_status_value == "created" then "Specs-only PR created by the trusted launcher on branch \($branch_value)." else empty end)
            ] | join(" ")
            '
    )"

    jq \
        --arg issue_status_value "${issue_status_value}" \
        --arg issue_url_value "${issue_url_value}" \
        --arg pr_status_value "${pr_status_value}" \
        --arg pr_url_value "${pr_url_value}" \
        --arg branch_value "${branch_value}" \
        --arg error_value "${error_value}" \
        --arg warning_value "${warning_value}" \
        --argjson issue_number_value "${issue_number_value}" \
        --argjson pr_number_value "${pr_number_value}" \
        '
        .github.issue_status = $issue_status_value
        | .github.issue_number = $issue_number_value
        | .github.issue_url = (if $issue_url_value == "" then null else $issue_url_value end)
        | .github.pr_status = $pr_status_value
        | .github.pr_number = $pr_number_value
        | .github.pr_url = (if $pr_url_value == "" then null else $pr_url_value end)
        | .github.branch = (if $branch_value == "" then null else $branch_value end)
        | .github.error = (if $error_value == "" then null else $error_value end)
        | if $warning_value != "" and (.warnings | index($warning_value)) == null then
              .warnings += [$warning_value]
          else
              .
          end
        | if .status == "failed" then
              .
          elif (.github.issue_status == "failed") or (.github.pr_status == "failed") or (.warnings | length > 0) or (.open_questions | length > 0) then
              .status = "complete-with-warnings"
          else
              .status = "complete"
          end
        ' "${source_file}" >"${destination_file}"
}

tmp_prompt_file="$(mktemp)"
tmp_codex_output="$(mktemp)"
tmp_last_message="$(mktemp)"
tmp_final_message="$(mktemp)"
tmp_codex_home="$(mktemp -d)"

cat >"${tmp_prompt_file}" <<EOM
Read and execute the repository wrapper and skill instructions at:
- ${bmalph_commands_path}
- ${wrapper_path}
- ${skill_path}

You are running a fully autonomous BMALPH planning session. Do not pause for human confirmation.

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
- keep file writes scoped to ${bundle_dir}; GitHub side effects are brokered by the trusted launcher after the planning run
- use the existing BMALPH wrapper in ${bmalph_commands_path} as the canonical process guide, and only inspect raw _bmad workflow files when the wrapper instructions prove insufficient
- start from the BMALPH wrapper surface and route the planning flow through the wrapper commands 'bmalph', 'analyst', 'create-brief', 'create-prd', 'create-architecture', 'create-epics-stories', and 'implementation-readiness'
- do not let interactive menus or phase gates block progress
- keep context intentionally small; do not bulk-scan the entire repository
- do not read .claude/skills/AI-AGENT-GUIDE.md or .claude/skills/SKILL-DECISION-GUIDE.md in this child run; the launcher already selected the correct skill
- do not reopen general repository meta-guides unless the planning skill explicitly needs them to unblock execution
- prefer the BMALPH wrapper command names over direct workflow paths in your reasoning and artifact traceability
- inspect only the minimum relevant docs and code paths needed to justify the plan
- infer the likely feature area from the task description and start with the 1-3 most likely paths
- stop discovery once you have enough evidence to write the bundle; avoid marginal context gathering
- when a workflow would normally halt for input, decide internally and continue
- answer subagent questions yourself from repository context whenever possible
- validate research, brief, PRD, architecture, epics, and stories for between 1 and ${max_validation_rounds} rounds
- create ${bundle_dir}/run-summary.md with the reasoning trace, validation round counts, warnings, and open questions
- do not create GitHub issues or PRs yourself in this child run; prepare the bundle and supporting summaries only
- report github.issue_status and github.pr_status as skipped in the child JSON unless the launcher explicitly instructed you to do otherwise

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

if [ -f "${HOME}/.codex/auth.json" ]; then
    mkdir -p "${tmp_codex_home}/.codex"
    cp "${HOME}/.codex/auth.json" "${tmp_codex_home}/.codex/auth.json"
    chmod 600 "${tmp_codex_home}/.codex/auth.json" >/dev/null 2>&1 || true
fi

HOME="${tmp_codex_home}" cs_sync_host_codex_auth

sanitized_env=(
    env -i
    HOME="${tmp_codex_home}"
    PATH="${PATH}"
    LANG="${LANG:-C.UTF-8}"
    LC_ALL="${LC_ALL:-C.UTF-8}"
    TERM="${TERM:-dumb}"
    TMPDIR="${TMPDIR:-/tmp}"
)
if [ -n "${OPENAI_API_KEY:-}" ]; then
    sanitized_env+=(OPENAI_API_KEY="${OPENAI_API_KEY}")
fi
if [ -n "${CODEX_ARGS_FILE:-}" ]; then
    sanitized_env+=(CODEX_ARGS_FILE="${CODEX_ARGS_FILE}")
fi
if [ -n "${CODEX_PROMPT_FILE:-}" ]; then
    sanitized_env+=(CODEX_PROMPT_FILE="${CODEX_PROMPT_FILE}")
fi
if [ -n "${CODEX_ENV_FILE:-}" ]; then
    sanitized_env+=(CODEX_ENV_FILE="${CODEX_ENV_FILE}")
fi

if ! "${sanitized_env[@]}" "${codex_cmd[@]}" <"${tmp_prompt_file}" >"${tmp_codex_output}" 2>&1; then
    echo "Error: autonomous BMAD planning run failed." >&2
    sed -n '1,160p' "${tmp_codex_output}" >&2
    exit 1
fi

if [ ! -s "${tmp_last_message}" ]; then
    echo "Error: Codex did not produce a final JSON message." >&2
    sed -n '1,160p' "${tmp_codex_output}" >&2
    exit 1
fi

issue_status="skipped"
issue_number="null"
issue_url=""
pr_status="skipped"
pr_number="null"
pr_url=""
pr_branch=""
github_error=""

if [ "${issue_mode}" = "create" ]; then
    if issue_url="$(create_github_issue "${tmp_last_message}")"; then
        issue_status="created"
        issue_number="$(printf '%s\n' "${issue_url}" | sed -nE 's#.*/issues/([0-9]+)$#\1#p')"
        issue_number="${issue_number:-null}"
    else
        issue_status="failed"
        github_error="Issue creation failed: ${issue_url}"
        issue_url=""
        issue_number="null"
    fi
fi

if [ "${pr_mode}" = "draft" ]; then
    pr_branch="specs/${bundle_id}"
    issue_ref=""
    if [ "${issue_number}" != "null" ]; then
        issue_ref="${issue_number}"
    fi

    if pr_url="$(create_specs_pr "${tmp_last_message}" "${issue_ref}")"; then
        pr_status="created"
        pr_number="$(printf '%s\n' "${pr_url}" | sed -nE 's#.*/pull/([0-9]+)$#\1#p')"
        pr_number="${pr_number:-null}"
    else
        pr_status="failed"
        if [ -n "${github_error}" ]; then
            github_error="${github_error}; PR creation failed: ${pr_url}"
        else
            github_error="PR creation failed: ${pr_url}"
        fi
        pr_url=""
        pr_number="null"
    fi
fi

finalize_result_json \
    "${tmp_last_message}" \
    "${tmp_final_message}" \
    "${issue_status}" \
    "${issue_number}" \
    "${issue_url}" \
    "${pr_status}" \
    "${pr_number}" \
    "${pr_url}" \
    "${pr_branch}" \
    "${github_error}"

if [ -n "${result_file}" ]; then
    mkdir -p "$(dirname "${result_file}")"
    cp "${tmp_final_message}" "${result_file}"
fi

cat "${tmp_final_message}"
