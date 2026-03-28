#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../.." && pwd)"
# shellcheck source=scripts/local-coder/lib/github-auth.sh
. "${ROOT_DIR}/scripts/local-coder/lib/github-auth.sh"
# shellcheck source=scripts/local-coder/lib/bmalph.sh
. "${ROOT_DIR}/scripts/local-coder/lib/bmalph.sh"

SETTINGS_FILE="${ROOT_DIR}/.devcontainer/workspace-settings.env"
if [ -f "${SETTINGS_FILE}" ]; then
    # shellcheck disable=SC1090
    . "${SETTINGS_FILE}"
fi

cs_bmalph_load_defaults

platform="${BMALPH_DEFAULT_PLATFORM}"
project_dir="${ROOT_DIR}"
project_name="${BMALPH_DEFAULT_PROJECT_NAME}"
project_description="${BMALPH_DEFAULT_PROJECT_DESCRIPTION}"
upgrade=false
run_init=false
dry_run=false
skip_verify=false

usage() {
    cat <<'EOM'
Usage: bash scripts/local-coder/install-bmalph.sh [options]

Install the BMALPH CLI for local Codex or Claude development and optionally
preview or run project initialization.

Options:
  --platform <codex|claude-code>  Target platform for dry-run/init checks
  --project-dir <path>            Project directory for optional init
  --project-name <name>           Project name passed to bmalph init
  --project-description <text>    Project description passed to bmalph init
  --upgrade                       Reinstall/upgrade the global bmalph package
  --init                          Run 'bmalph init' in the target project dir
  --dry-run                       Use a non-destructive init preview
  --skip-verify                   Skip the post-install dry-run verification
  -h, --help                      Show this help text

Examples:
  bash scripts/local-coder/install-bmalph.sh --platform codex
  bash scripts/local-coder/install-bmalph.sh --platform claude-code
  bash scripts/local-coder/install-bmalph.sh --platform codex --init --dry-run
EOM
}

while [ $# -gt 0 ]; do
    case "$1" in
        --platform)
            platform="${2:?Missing value for --platform}"
            shift 2
            ;;
        --project-dir)
            project_dir="${2:?Missing value for --project-dir}"
            shift 2
            ;;
        --project-name)
            project_name="${2:?Missing value for --project-name}"
            shift 2
            ;;
        --project-description)
            project_description="${2:?Missing value for --project-description}"
            shift 2
            ;;
        --upgrade)
            upgrade=true
            shift
            ;;
        --init)
            run_init=true
            shift
            ;;
        --dry-run)
            dry_run=true
            shift
            ;;
        --skip-verify)
            skip_verify=true
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

case "${platform}" in
    codex|claude-code)
        ;;
    *)
        echo "Error: unsupported platform '${platform}'. Use 'codex' or 'claude-code'." >&2
        exit 1
        ;;
esac

export CS_USER_NPM_GLOBAL_BIN="${HOME}/.npm-global/bin"
export PATH="${CS_USER_NPM_GLOBAL_BIN}:${PATH}"

if [ "${upgrade}" = true ] && command -v bmalph >/dev/null 2>&1; then
    cs_require_command npm
    mkdir -p "${HOME}/.npm-global"
    npm config set prefix "${HOME}/.npm-global" >/dev/null 2>&1 || true
    npm install -g "${BMALPH_NPM_PACKAGE}"
else
    cs_ensure_bmalph_cli
fi

echo "BMALPH installed:"
echo "  - command: $(command -v bmalph)"
echo "  - version: $(bmalph --version)"
echo "  - package: ${BMALPH_NPM_PACKAGE}"

platform_cli_hint="$(cs_bmalph_platform_cli_hint "${platform}")"
if [ -n "${platform_cli_hint}" ] && ! command -v "${platform_cli_hint}" >/dev/null 2>&1; then
    echo "Warning: expected platform CLI '${platform_cli_hint}' is not installed or not in PATH." >&2
fi

if [ "${skip_verify}" != true ]; then
    cs_verify_bmalph_dry_run "${platform}" "${project_name}" "${project_description}"
    echo "BMALPH dry-run verification passed for platform '${platform}'."
fi

if [ "${dry_run}" = true ] && [ "${run_init}" != true ]; then
    echo "Warning: --dry-run has no effect unless --init is also provided." >&2
fi

if [ "${run_init}" = true ]; then
    echo "Running BMALPH init in '${project_dir}' for platform '${platform}'."
    init_cmd=(
        bmalph
        -C "${project_dir}"
        init
        --platform "${platform}"
        --name "${project_name}"
        --description "${project_description}"
    )

    if [ "${dry_run}" = true ]; then
        init_cmd+=(--dry-run)
    fi

    "${init_cmd[@]}"
else
    echo "BMALPH CLI is ready."
    echo "To preview project initialization, run:"
    echo "  make bmalph-init BMALPH_PLATFORM=${platform} BMALPH_DRY_RUN=true"
    echo "  # or"
    echo "  bash scripts/local-coder/install-bmalph.sh --platform ${platform} --init --dry-run"
fi
