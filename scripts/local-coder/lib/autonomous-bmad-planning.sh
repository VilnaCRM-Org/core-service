#!/usr/bin/env bash
# shellcheck shell=bash

cs_abp_resolve_config_file() {
    local root_dir="${1:?Missing root directory}"

    if [ -f "${root_dir}/_bmad/config.yaml" ]; then
        printf '%s\n' "${root_dir}/_bmad/config.yaml"
        return 0
    fi

    if [ -f "${root_dir}/_bmad/bmm/config.yaml" ]; then
        printf '%s\n' "${root_dir}/_bmad/bmm/config.yaml"
        return 0
    fi

    cat >&2 <<'EOM'
Error: unable to locate BMAD config. Expected '_bmad/config.yaml' or '_bmad/bmm/config.yaml'.
Run 'make bmalph-setup' first if BMAD assets are not initialized.
EOM
    return 1
}

cs_abp_resolve_bmalph_commands_file() {
    local root_dir="${1:?Missing root directory}"

    if [ -f "${root_dir}/_bmad/COMMANDS.md" ]; then
        printf '%s\n' "${root_dir}/_bmad/COMMANDS.md"
        return 0
    fi

    cat >&2 <<'EOM'
Error: unable to locate BMALPH command wrapper file. Expected '_bmad/COMMANDS.md'.
Run 'make bmalph-setup' first if BMALPH assets are not initialized.
EOM
    return 1
}

cs_abp_read_config_value() {
    local config_file="${1:?Missing config file}"
    local key="${2:?Missing config key}"

    sed -n "s/^${key}:[[:space:]]*//p" "${config_file}" | head -n 1 | sed -E 's/^"(.*)"$/\1/'
}

cs_abp_absolutize_path() {
    local root_dir="${1:?Missing root directory}"
    local path_value="${2:?Missing path value}"

    case "${path_value}" in
        /*)
            printf '%s\n' "${path_value}"
            ;;
        *)
            printf '%s\n' "${root_dir}/${path_value}"
            ;;
    esac
}

cs_abp_slugify() {
    local value="${1:-}"

    value="$(printf '%s' "${value}" | tr '[:upper:]' '[:lower:]')"
    value="$(printf '%s' "${value}" | sed -E 's/[^a-z0-9]+/-/g; s/^-+//; s/-+$//; s/-{2,}/-/g')"

    if [ -z "${value}" ]; then
        value="plan"
    fi

    printf '%.48s\n' "${value}"
}

cs_abp_validate_rounds() {
    local rounds="${1:?Missing validation round count}"

    if ! [[ "${rounds}" =~ ^[0-9]+$ ]]; then
        echo "Error: validation rounds must be an integer from 1 to 3." >&2
        return 1
    fi

    if [ "${rounds}" -lt 1 ] || [ "${rounds}" -gt 3 ]; then
        echo "Error: validation rounds must be between 1 and 3." >&2
        return 1
    fi
}

cs_abp_default_repo_slug() {
    local root_dir="${1:?Missing root directory}"
    local remote_url=""

    if ! remote_url="$(git -C "${root_dir}" remote get-url origin 2>/dev/null)"; then
        return 0
    fi

    remote_url="${remote_url%.git}"
    remote_url="$(printf '%s' "${remote_url}" | sed -E \
        -e 's#^git@([^:]+):#\1/#' \
        -e 's#^https?://([^/@]+@)?([^/]+)/#\2/#')"

    if [[ "${remote_url}" == github.com/* ]]; then
        remote_url="${remote_url#github.com/}"
    fi

    if [[ "${remote_url}" == */* ]]; then
        printf '%s\n' "${remote_url}"
    fi
}

cs_abp_seed_paths_from_task() {
    local root_dir="${1:?Missing root directory}"
    local task="${2:-}"
    local normalized_task=""
    local -a candidates=()
    local candidate=""

    normalized_task="$(printf '%s' "${task}" | tr '[:upper:]' '[:lower:]')"

    if [[ "${normalized_task}" == *doc* ]] || [[ "${normalized_task}" == *guide* ]] || [[ "${normalized_task}" == *readme* ]] || [[ "${normalized_task}" == *onboarding* ]]; then
        candidates+=(
            "README.md"
            "AGENTS.md"
            "docs/getting-started.md"
            "docs/onboarding.md"
            "docs/developer-guide.md"
        )
    fi

    if [[ "${normalized_task}" == *cli* ]] || [[ "${normalized_task}" == *command* ]] || [[ "${normalized_task}" == *script* ]] || [[ "${normalized_task}" == *make* ]] || [[ "${normalized_task}" == *autonomous* ]] || [[ "${normalized_task}" == *bmad* ]] || [[ "${normalized_task}" == *bmalph* ]] || [[ "${normalized_task}" == *bundle* ]] || [[ "${normalized_task}" == *maintainer* ]]; then
        candidates+=(
            "Makefile"
            "_bmad/COMMANDS.md"
            "scripts/local-coder/run-autonomous-bmad-planning.sh"
            "scripts/local-coder/lib/autonomous-bmad-planning.sh"
            ".claude/skills/bmad-autonomous-planning/SKILL.md"
            ".agents/skills/bmad-autonomous-planning/SKILL.md"
        )
    fi

    if [ "${#candidates[@]}" -eq 0 ]; then
        return 0
    fi

    printf '%s\n' "${candidates[@]}" | awk '!seen[$0]++' | while IFS= read -r candidate; do
        if [ -e "${root_dir}/${candidate}" ]; then
            printf '%s\n' "${candidate}"
        fi
    done
}
