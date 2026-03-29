#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

setup_isolated_bmalph_env() {
  if [ "${HOME+x}" = "x" ]; then
    BMALPH_ORIGINAL_HOME="${HOME}"
    BMALPH_ORIGINAL_HOME_SET=1
  else
    BMALPH_ORIGINAL_HOME=""
    BMALPH_ORIGINAL_HOME_SET=0
  fi

  BMALPH_TEST_HOME="$(mktemp -d)"
  export HOME="${BMALPH_TEST_HOME}"
  export CS_USER_NPM_GLOBAL_BIN="${BMALPH_TEST_HOME}/.npm-global/bin"
  mkdir -p "${CS_USER_NPM_GLOBAL_BIN}"
}

setup_autonomous_bmad_fixture() {
  local repo_root

  repo_root="$(pwd)"

  if [ -f "${repo_root}/_bmad/config.yaml" ] || [ -f "${repo_root}/_bmad/bmm/config.yaml" ]; then
    BMAD_TEST_CREATED_CONFIG=0
    return 0
  fi

  mkdir -p "${repo_root}/_bmad"
  cat >"${repo_root}/_bmad/config.yaml" <<'EOF'
planning_artifacts: _bmad-output/planning-artifacts
EOF
  BMAD_TEST_CREATED_CONFIG=1
}

teardown() {
  if [ "${BMAD_TEST_CREATED_CONFIG:-0}" = "1" ] && [ -f "_bmad/config.yaml" ]; then
    rm -f "_bmad/config.yaml"
    rmdir "_bmad" >/dev/null 2>&1 || true
  fi

  if [ -n "${BMALPH_TEST_HOME:-}" ] && [ -d "${BMALPH_TEST_HOME}" ]; then
    rm -rf "${BMALPH_TEST_HOME}"
  fi

  if [ "${BMALPH_ORIGINAL_HOME_SET:-0}" = "1" ]; then
    export HOME="${BMALPH_ORIGINAL_HOME}"
  else
    unset HOME
  fi

  unset BMAD_TEST_CREATED_CONFIG BMALPH_ORIGINAL_HOME BMALPH_ORIGINAL_HOME_SET BMALPH_TEST_HOME CS_USER_NPM_GLOBAL_BIN
}

@test "make help lists BMALPH targets" {
  run make help
  assert_success
  assert_output --partial "bmalph-install"
  assert_output --partial "bmalph-codex"
  assert_output --partial "bmalph-claude"
  assert_output --partial "bmalph-init"
  assert_output --partial "bmalph-setup"
  assert_output --partial "bmad-autonomous-plan"
}

@test "make bmalph-install installs and verifies BMALPH for codex" {
  setup_isolated_bmalph_env
  run make bmalph-install BMALPH_PLATFORM=codex
  assert_success
  assert_output --partial "BMALPH installed:"
  assert_output --partial "BMALPH dry-run verification passed for platform 'codex'."
  assert_output --partial "BMALPH CLI is ready."
}

@test "make bmalph-codex installs and verifies the Codex BMALPH flow" {
  setup_isolated_bmalph_env
  run make bmalph-codex
  assert_success
  assert_output --partial 'install-bmalph.sh --platform "codex"'
  assert_output --partial "BMALPH dry-run verification passed for platform 'codex'."
}

@test "make bmalph-claude installs and verifies the Claude BMALPH flow" {
  setup_isolated_bmalph_env
  run make bmalph-claude
  assert_success
  assert_output --partial 'install-bmalph.sh --platform "claude-code"'
  assert_output --partial "BMALPH dry-run verification passed for platform 'claude-code'."
}

@test "make bmalph-init supports dry-run without changing tracked files" {
  local before_status after_status

  setup_isolated_bmalph_env
  before_status="$(git status --short --untracked-files=all)"

  run make bmalph-init BMALPH_PLATFORM=codex BMALPH_DRY_RUN=true
  assert_success
  assert_output --partial "Running BMALPH init in"
  if [[ "${output}" != *"[dry-run] Would perform the following actions:"* ]] && [[ "${output}" != *"bmalph is already initialized in this project."* ]]; then
    echo "Unexpected bmalph-init output:" >&3
    printf '%s\n' "${output}" >&3
    false
  fi

  after_status="$(git status --short --untracked-files=all)"
  [ "${before_status}" = "${after_status}" ]
}

@test "make bmalph-setup supports one-command dry-run without changing tracked files" {
  local before_status after_status

  setup_isolated_bmalph_env
  before_status="$(git status --short --untracked-files=all)"

  run make bmalph-setup BMALPH_PLATFORM=codex BMALPH_DRY_RUN=true
  assert_success
  assert_output --partial 'install-bmalph.sh --platform "codex" --init --dry-run'
  if [[ "${output}" != *"[dry-run] Would perform the following actions:"* ]] && [[ "${output}" != *"bmalph is already initialized in this project."* ]]; then
    echo "Unexpected bmalph-setup output:" >&3
    printf '%s\n' "${output}" >&3
    false
  fi

  after_status="$(git status --short --untracked-files=all)"
  [ "${before_status}" = "${after_status}" ]
}

@test "make bmalph-setup restores generated BMALPH assets in a fresh worktree" {
  setup_isolated_bmalph_env
  run bash -lc '
    set -euo pipefail
    repo_root="$(pwd)"
    tmpdir="$(mktemp -d)"
    doctor_output="$(mktemp)"
    before_status=""
    after_status=""
    patch_file="$tmpdir.patch"
    cleanup() {
      git -C "$repo_root" worktree remove --force "$tmpdir" >/dev/null 2>&1 || true
      rm -rf "$tmpdir" "$doctor_output" "$patch_file"
    }
    trap cleanup EXIT

    git -C "$repo_root" worktree add --detach "$tmpdir" HEAD >/dev/null
    git -C "$repo_root" diff --binary HEAD >"$patch_file"
    if [ -s "$patch_file" ]; then
      git -C "$tmpdir" apply --whitespace=nowarn "$patch_file"
    fi
    git -C "$tmpdir" config user.name "BMALPH Validation"
    git -C "$tmpdir" config user.email "bmalph-validation@example.com"
    if [ -n "$(git -C "$tmpdir" status --short --untracked-files=all)" ]; then
      git -C "$tmpdir" add -A
      git -C "$tmpdir" commit -m "Temporary validation snapshot" >/dev/null
    fi
    cd "$tmpdir"

    [ ! -e _bmad ]
    [ ! -e .ralph ]
    before_status="$(git status --short --untracked-files=all)"

    make bmalph-setup BMALPH_PLATFORM=codex
    export PATH="$(npm config get prefix)/bin:$PATH"
    bmalph doctor >"$doctor_output"
    cat "$doctor_output"
    grep -F "all checks OK" "$doctor_output"
    after_status="$(git status --short --untracked-files=all)"
    [ "$before_status" = "$after_status" ]
  '
  assert_success
  assert_output --partial "BMALPH project assets are incomplete after init; running 'bmalph upgrade --force' to restore local files."
  assert_output --partial "Restoring tracked file modified only by BMALPH setup: AGENTS.md"
  assert_output --partial "all checks OK"
}

@test "BMALPH generated paths stay ignored for local installs" {
  run bash -lc 'grep -Fx ".ralph/" .gitignore && grep -Fx ".ralph/logs/" .gitignore && grep -Fx "_bmad/" .gitignore && grep -Fx "_bmad-output/" .gitignore'
  assert_success
}

@test "make bmalph-setup refuses to run init over dirty tracked files" {
  setup_isolated_bmalph_env
  run bash -lc '
    set -euo pipefail
    repo_root="$(pwd)"
    tmpdir="$(mktemp -d)"
    patch_file="$tmpdir.patch"
    cleanup() {
      git -C "$repo_root" worktree remove --force "$tmpdir" >/dev/null 2>&1 || true
      rm -rf "$tmpdir" "$patch_file"
    }
    trap cleanup EXIT

    git -C "$repo_root" worktree add --detach "$tmpdir" HEAD >/dev/null
    git -C "$repo_root" diff --binary HEAD >"$patch_file"
    if [ -s "$patch_file" ]; then
      git -C "$tmpdir" apply --whitespace=nowarn "$patch_file"
    fi

    cd "$tmpdir"
    printf "\n# dirty\n" >> README.md

    set +e
    output="$(make bmalph-setup BMALPH_PLATFORM=codex 2>&1)"
    status=$?
    set -e

    printf "%s\n" "$output"
    [ "$status" -ne 0 ]
    grep -F "Error: refusing to run BMALPH init with existing tracked changes." <<<"$output"
  '
  assert_success
  assert_output --partial "Error: refusing to run BMALPH init with existing tracked changes."
}

@test "workspace port helper auto-selects non-conflicting Docker host ports" {
  run bash -lc '
    set -euo pipefail
    source scripts/local-coder/lib/workspace-ports.sh
    unset HTTP_PORT HTTPS_PORT HTTP3_PORT DB_PORT REDIS_PORT LOCALSTACK_PORT STRUCTURIZR_PORT
    cs_configure_workspace_port_overrides "80 443 6379 8080 27017 4566"
    [ "$HTTP_PORT" = "18080" ]
    [ "$HTTPS_PORT" = "18443" ]
    [ "$HTTP3_PORT" = "18443" ]
    [ "$DB_PORT" = "37017" ]
    [ "$REDIS_PORT" = "36379" ]
    [ "$LOCALSTACK_PORT" = "14566" ]
    [ "$STRUCTURIZR_PORT" = "18081" ]
  '
  assert_success
}

@test "workspace port helper mirrors single HTTPS or HTTP3 values into both env vars" {
  run bash -lc '
    set -euo pipefail
    source scripts/local-coder/lib/workspace-ports.sh

    unset HTTPS_PORT HTTP3_PORT
    reserved_ports=""
    HTTPS_PORT=9443
    cs_ensure_workspace_https_ports reserved_ports
    [ "$HTTPS_PORT" = "9443" ]
    [ "$HTTP3_PORT" = "9443" ]
    env | grep -Fx "HTTPS_PORT=9443"
    env | grep -Fx "HTTP3_PORT=9443"
    [ "$reserved_ports" = "9443" ]

    unset HTTPS_PORT HTTP3_PORT
    reserved_ports=""
    HTTP3_PORT=10443
    cs_ensure_workspace_https_ports reserved_ports
    [ "$HTTPS_PORT" = "10443" ]
    [ "$HTTP3_PORT" = "10443" ]
    env | grep -Fx "HTTPS_PORT=10443"
    env | grep -Fx "HTTP3_PORT=10443"
    [ "$reserved_ports" = "10443" ]
  '
  assert_success
}

@test "workspace bootstrap persists resolved host port overrides for login shells" {
  run bash -lc '
    set -euo pipefail
    repo_root="$(pwd)"
    temp_home="$(mktemp -d)"
    mock_bin="$(mktemp -d)"
    cleanup() {
      rm -rf "$temp_home" "$mock_bin"
    }
    trap cleanup EXIT

    cat >"$mock_bin/gh" <<'"'"'EOF'"'"'
#!/usr/bin/env bash
set -euo pipefail
case "${1-} ${2-}" in
  "api user")
    exit 0
    ;;
  "auth setup-git")
    exit 0
    ;;
  "config set")
    exit 0
    ;;
  *)
    exit 0
    ;;
esac
EOF

    cat >"$mock_bin/codex" <<'"'"'EOF'"'"'
#!/usr/bin/env bash
set -euo pipefail
case "${1-} ${2-}" in
  "login status")
    exit 0
    ;;
  "--version ")
    echo "codex-cli 0.117.0"
    exit 0
    ;;
  *)
    echo "codex-cli 0.117.0"
    exit 0
    ;;
esac
EOF

    cat >"$mock_bin/bmalph" <<'"'"'EOF'"'"'
#!/usr/bin/env bash
set -euo pipefail
echo "2.11.0"
EOF

    cat >"$mock_bin/docker" <<'"'"'EOF'"'"'
#!/usr/bin/env bash
set -euo pipefail
case "${1-}" in
  info)
    exit 0
    ;;
  ps)
    printf "%s\n" "container-1"
    exit 0
    ;;
  inspect)
    cat <<'"'"'EOM'"'"'
80
443
6379
8080
27017
4566
EOM
    exit 0
    ;;
  *)
    exit 0
    ;;
esac
EOF

    chmod +x "$mock_bin/gh" "$mock_bin/codex" "$mock_bin/bmalph" "$mock_bin/docker"

    env \
      -u HTTP_PORT \
      -u HTTPS_PORT \
      -u HTTP3_PORT \
      -u DB_PORT \
      -u REDIS_PORT \
      -u LOCALSTACK_PORT \
      -u STRUCTURIZR_PORT \
      HOME="$temp_home" \
      PATH="$mock_bin:/usr/bin:/bin" \
      OPENCLAW_WORKSPACE_ROOT="$repo_root" \
      bash scripts/local-coder/setup-secure-agent-env.sh

    secrets_file="$temp_home/.config/core-service/agent-secrets.env"
    [ -f "$secrets_file" ]
    grep -Fx "export HTTP_PORT=18080" "$secrets_file"
    grep -Fx "export HTTPS_PORT=18443" "$secrets_file"
    grep -Fx "export HTTP3_PORT=18443" "$secrets_file"
    grep -Fx "export DB_PORT=37017" "$secrets_file"
    grep -Fx "export REDIS_PORT=36379" "$secrets_file"
    grep -Fx "export LOCALSTACK_PORT=14566" "$secrets_file"
    grep -Fx "export STRUCTURIZR_PORT=18081" "$secrets_file"
  '
  assert_success
  assert_output --partial "Workspace host ports:"
}

@test "workspace compose files avoid fixed localstack names and parameterize redis host ports" {
  run bash -lc '
    set -euo pipefail
    ! grep -En "container_name:[[:space:]]*localstack" docker-compose.override.yml docker-compose.load_test.override.yml
    grep -En "\\$\\{REDIS_PORT:-6379\\}:6379" docker-compose.yml
  '
  assert_success
}

@test "devcontainer initialize creates optional host mount directories for fresh workspaces" {
  run bash -lc '
    set -euo pipefail
    temp_home="$(mktemp -d)"
    trap "rm -rf \"$temp_home\"" EXIT
    HOME="$temp_home" bash .devcontainer/initialize.sh
    [ -d "$temp_home/.openclaw-host-secrets" ]
    [ -d "$temp_home/.openclaw-host-codex" ]
    [ "$(stat -c "%a" "$temp_home/.openclaw-host-secrets")" = "700" ]
    [ "$(stat -c "%a" "$temp_home/.openclaw-host-codex")" = "700" ]
    grep -En "\"initializeCommand\": \"bash \\.devcontainer/initialize\\.sh\"" .devcontainer/devcontainer.json
  '
  assert_success
}

@test "devcontainer keeps workspace path host-visible for docker-outside-of-docker" {
  run bash -lc '
    set -euo pipefail
    grep -En "\"workspaceMount\": \"source=\\$\\{localWorkspaceFolder\\},target=\\$\\{localWorkspaceFolder\\},type=bind\"" .devcontainer/devcontainer.json
    grep -En "\"workspaceFolder\": \"\\$\\{localWorkspaceFolder\\}\"" .devcontainer/devcontainer.json
  '
  assert_success
}

@test "workspace bootstrap normalizes GitHub remotes to https for token-auth devcontainers" {
  run bash -lc '
    set -euo pipefail
    repo_root="$(pwd)"
    temp_repo="$(mktemp -d)"
    trap "rm -rf \"$temp_repo\"" EXIT
    git -c init.defaultBranch=main init "$temp_repo" >/dev/null
    git -C "$temp_repo" remote add origin git@github.com:VilnaCRM-Org/core-service.git
    source scripts/local-coder/lib/github-auth.sh
    cd "$temp_repo"
    cs_align_git_remote_protocol origin github.com https
    [ "$(git remote get-url origin)" = "https://github.com/VilnaCRM-Org/core-service.git" ]
    [ "$(git remote get-url --push origin)" = "https://github.com/VilnaCRM-Org/core-service.git" ]
    grep -En "^GH_GIT_PROTOCOL=\"\\$\\{GH_GIT_PROTOCOL:-https\\}\"$" "$repo_root/.devcontainer/workspace-settings.env"
  '
  assert_success
}

@test "make preserves workspace host port overrides from the environment" {
  run bash -lc '
    set -euo pipefail
    HTTP_PORT=18080 \
    HTTPS_PORT=18443 \
    HTTP3_PORT=18443 \
    DB_PORT=37017 \
    REDIS_PORT=36379 \
    LOCALSTACK_PORT=14566 \
    STRUCTURIZR_PORT=18081 \
    make -pn >/tmp/make-vars.out
    grep -Fx "HTTP_PORT := 18080" /tmp/make-vars.out
    grep -Fx "HTTPS_PORT := 18443" /tmp/make-vars.out
    grep -Fx "HTTP3_PORT := 18443" /tmp/make-vars.out
    grep -Fx "DB_PORT := 37017" /tmp/make-vars.out
    grep -Fx "REDIS_PORT := 36379" /tmp/make-vars.out
    grep -Fx "LOCALSTACK_PORT := 14566" /tmp/make-vars.out
    grep -Fx "STRUCTURIZR_PORT := 18081" /tmp/make-vars.out
    rm -f /tmp/make-vars.out
  '
  assert_success
}

@test "BMALPH wrapper skills explain how to bootstrap missing local assets" {
  run bash -lc '
    missing=0
    while IFS= read -r file; do
      if grep -q "_bmad/" "$file" && ! grep -q "make bmalph-setup" "$file"; then
        echo "$file"
        missing=1
      fi
    done < <(find .agents/skills -name SKILL.md -print | sort)
    exit "$missing"
  '
  assert_success
}

@test "autonomous planning launcher dry-run resolves bundle metadata" {
  setup_isolated_bmalph_env
  setup_autonomous_bmad_fixture
  rm -rf _bmad-output/planning-artifacts/autonomous/test-autonomous-plan
  run bash scripts/local-coder/run-autonomous-bmad-planning.sh \
    --task "Plan autonomous BMAD specs" \
    --bundle-id test-autonomous-plan \
    --max-validation-rounds 2 \
    --issue-mode skip \
    --pr-mode skip \
    --dry-run
  assert_success
  assert_output --partial "Dry run only."
  assert_output --partial "Bundle ID: test-autonomous-plan"
  assert_output --partial "/.claude/skills/bmad-autonomous-planning/SKILL.md"
  assert_output --partial "/scripts/local-coder/schemas/autonomous-bmad-planning-result.schema.json"
}

@test "autonomous planning launcher rejects invalid validation rounds" {
  setup_isolated_bmalph_env
  setup_autonomous_bmad_fixture
  run bash scripts/local-coder/run-autonomous-bmad-planning.sh \
    --task "Plan autonomous BMAD specs" \
    --max-validation-rounds 4 \
    --dry-run
  [ "$status" -ne 0 ]
  assert_output --partial "validation rounds must be between 1 and 3"
}

@test "make bmad-autonomous-plan supports dry-run execution" {
  setup_isolated_bmalph_env
  setup_autonomous_bmad_fixture
  rm -rf _bmad-output/planning-artifacts/autonomous/test-autonomous-plan-make
  run make bmad-autonomous-plan \
    PLAN_TASK="Plan autonomous BMAD specs" \
    PLAN_BUNDLE_ID="test-autonomous-plan-make" \
    PLAN_VALIDATION_ROUNDS=1 \
    PLAN_DRY_RUN=true
  assert_success
  assert_output --partial "Dry run only."
  assert_output --partial "Bundle ID: test-autonomous-plan-make"
}

@test "autonomous planning launcher invokes codex exec with skill prompt and schema" {
  setup_autonomous_bmad_fixture
  rm -rf _bmad-output/planning-artifacts/autonomous/test-autonomous-plan-codex
  run bash -lc '
    set -euo pipefail
    repo_root="$(pwd)"
    temp_home="$(mktemp -d)"
    mock_bin="$(mktemp -d)"
    codex_args="${temp_home}/codex-args.txt"
    codex_prompt="${temp_home}/codex-prompt.txt"
    codex_env="${temp_home}/codex-env.txt"
    cleanup() {
      rm -rf "$temp_home" "$mock_bin"
    }
    trap cleanup EXIT

    cat >"$mock_bin/codex" <<'"'"'EOF'"'"'
#!/usr/bin/env bash
set -euo pipefail

if [ "${1-} ${2-}" = "login status" ]; then
  exit 0
fi

args_file="${CODEX_ARGS_FILE:?missing CODEX_ARGS_FILE}"
prompt_file="${CODEX_PROMPT_FILE:?missing CODEX_PROMPT_FILE}"
env_file="${CODEX_ENV_FILE:?missing CODEX_ENV_FILE}"
printf "%s\n" "$@" >"$args_file"
cat >"$prompt_file"
env | sort >"$env_file"

output_last_message=""
while [ $# -gt 0 ]; do
  case "$1" in
    --output-last-message)
      output_last_message="$2"
      shift 2
      ;;
    *)
      shift
      ;;
  esac
done

cat >"$output_last_message" <<'"'"'JSON'"'"'
{"status":"complete","task":"Plan autonomous BMAD specs","bundle_id":"test-autonomous-plan-codex","bundle_dir":"/tmp/test-autonomous-plan-codex","artifacts":{"research":"/tmp/test-autonomous-plan-codex/research.md","brief":"/tmp/test-autonomous-plan-codex/product-brief.md","distillate":null,"prd":"/tmp/test-autonomous-plan-codex/prd.md","architecture":"/tmp/test-autonomous-plan-codex/architecture.md","epics":"/tmp/test-autonomous-plan-codex/epics.md","implementation_readiness":"/tmp/test-autonomous-plan-codex/implementation-readiness.md","run_summary":"/tmp/test-autonomous-plan-codex/run-summary.md"},"validation_rounds":{"research":1,"brief":1,"prd":1,"architecture":1,"epics":1,"stories":1},"open_questions":[],"warnings":[],"github":{"issue_status":"skipped","issue_number":null,"issue_url":null,"pr_status":"skipped","pr_number":null,"pr_url":null,"branch":null,"error":null}}
JSON
EOF

    chmod +x "$mock_bin/codex"

    env \
      HOME="$temp_home" \
      PATH="$mock_bin:/usr/bin:/bin" \
      CODEX_ARGS_FILE="$codex_args" \
      CODEX_PROMPT_FILE="$codex_prompt" \
      CODEX_ENV_FILE="$codex_env" \
      bash scripts/local-coder/run-autonomous-bmad-planning.sh \
        --task "Plan autonomous BMAD specs" \
        --bundle-id "test-autonomous-plan-codex" \
        --max-validation-rounds 1 \
        --issue-mode create \
        --pr-mode draft \
        --repo "VilnaCRM-Org/core-service" \
        --base-branch "main" \
        --model "gpt-5.4-mini" \
        --result-file "${temp_home}/result.json"

    grep -Fx "exec" "$codex_args"
    grep -Fx -- "--model" "$codex_args"
    grep -Fx -- "gpt-5.4-mini" "$codex_args"
    grep -F -- "--output-schema" "$codex_args"
    grep -F -- "scripts/local-coder/schemas/autonomous-bmad-planning-result.schema.json" "$codex_args"
    grep -F -- "--dangerously-bypass-approvals-and-sandbox" "$codex_args"
    python3 - "$codex_args" <<'"'"'PY'"'"'
import pathlib
import sys

args = pathlib.Path(sys.argv[1]).read_text().splitlines()
assert args.index("--model") < args.index("-")
assert args.index("gpt-5.4-mini") < args.index("-")
PY
    grep -E "^HOME=" "$codex_env"
    if grep -Eq "^HOME=${temp_home}$" "$codex_env"; then
      echo "launcher should isolate Codex HOME" >&2
      exit 1
    fi
    if grep -Eq "^(GH_TOKEN|GITHUB_TOKEN|GH_AUTOMATION_TOKEN)=" "$codex_env"; then
      echo "launcher should not leak GitHub tokens into Codex env" >&2
      exit 1
    fi
    grep -F -- ".claude/skills/bmad-autonomous-planning/SKILL.md" "$codex_prompt"
    grep -F -- "Initial repository paths to inspect first:" "$codex_prompt"
    grep -F -- "- Makefile" "$codex_prompt"
    grep -F -- "- scripts/local-coder/run-autonomous-bmad-planning.sh" "$codex_prompt"
    grep -F -- "do not read .claude/skills/AI-AGENT-GUIDE.md or .claude/skills/SKILL-DECISION-GUIDE.md" "$codex_prompt"
    grep -F -- "do not create GitHub issues or PRs yourself in this child run" "$codex_prompt"
    grep -F -- "issue_mode: create" "$codex_prompt"
    grep -F -- "pr_mode: draft" "$codex_prompt"
    grep -F -- "Plan autonomous BMAD specs" "$codex_prompt"
    test -s "${temp_home}/result.json"
  '
  assert_success
  assert_output --partial "\"status\": \"complete-with-warnings\""
}

@test "make bmad-autonomous-plan forwards PLAN_REPO into dry-run output" {
  setup_isolated_bmalph_env
  setup_autonomous_bmad_fixture
  run make bmad-autonomous-plan \
    PLAN_TASK="Plan autonomous BMAD specs" \
    PLAN_REPO="VilnaCRM-Org/core-service" \
    PLAN_DRY_RUN=true
  assert_success
  assert_output --partial "Repo: VilnaCRM-Org/core-service"
}

@test "autonomous planning repo slug strips remote credentials" {
  run bash -lc '
    set -euo pipefail
    temp_repo="$(mktemp -d)"
    cleanup() {
      rm -rf "$temp_repo"
    }
    trap cleanup EXIT

    git init -q "$temp_repo"
    git -C "$temp_repo" remote add origin "https://username:token@github.com/VilnaCRM-Org/core-service.git"

    . scripts/local-coder/lib/autonomous-bmad-planning.sh
    actual="$(cs_abp_default_repo_slug "$temp_repo")"
    [ "$actual" = "VilnaCRM-Org/core-service" ]
  '
  assert_success
}
