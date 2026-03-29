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

teardown() {
  if [ -n "${BMALPH_TEST_HOME:-}" ] && [ -d "${BMALPH_TEST_HOME}" ]; then
    rm -rf "${BMALPH_TEST_HOME}"
  fi

  if [ "${BMALPH_ORIGINAL_HOME_SET:-0}" = "1" ]; then
    export HOME="${BMALPH_ORIGINAL_HOME}"
  else
    unset HOME
  fi

  unset BMALPH_ORIGINAL_HOME BMALPH_ORIGINAL_HOME_SET BMALPH_TEST_HOME CS_USER_NPM_GLOBAL_BIN
}

@test "make help lists BMALPH targets" {
  run make help
  assert_success
  assert_output --partial "bmalph-install"
  assert_output --partial "bmalph-codex"
  assert_output --partial "bmalph-claude"
  assert_output --partial "bmalph-init"
  assert_output --partial "bmalph-setup"
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
      if ! grep -q "_bmad/" "$file"; then
        continue
      fi
      if grep -q "make bmalph-setup" "$file"; then
        continue
      fi
      if grep -q "BMALPH assets must be initialized" "$file"; then
        continue
      fi
      if grep -q "BMALPH assets must be initialized for the repository" "$file"; then
        continue
      fi
      if grep -q "BMALPH assets must be initialized first" "$file"; then
        continue
      fi
      if grep -q "If \`_bmad/\` is missing" "$file"; then
        continue
      fi
      {
        echo "$file"
        missing=1
      }
    done < <(find .agents/skills -name SKILL.md -print | sort)
    exit "$missing"
  '
  assert_success
}

@test "autonomous planning wrapper skill documents the Codex subagent flow" {
  run bash -lc '
    set -euo pipefail
    wrapper=".agents/skills/bmad-autonomous-planning/SKILL.md"
    grep -F "repo-local bash" "$wrapper"
    grep -F "canonical planning contract" "$wrapper"
    grep -F "Read \`_bmad/COMMANDS.md\` and the resolved BMAD config file first." "$wrapper"
    grep -F "Run each BMALPH planning stage in a dedicated subagent" "$wrapper"
    grep -F "the main agent must decide the next step" "$wrapper"
    grep -F ".claude/skills/bmad-autonomous-planning/SKILL.md" "$wrapper"
    grep -F "Minimal Codex trigger example:" "$wrapper"
    ! grep -F "make bmalph-setup" "$wrapper"
  '
  assert_success
}

@test "autonomous planning skill contract is launcher-free and stage-oriented" {
  run bash -lc '
    set -euo pipefail
    skill=".claude/skills/bmad-autonomous-planning/SKILL.md"
    grep -F "Do not depend on repo-local" "$skill"
    grep -F "Spawn one focused subagent per BMALPH planning stage" "$skill"
    grep -F "The main agent is the user surrogate." "$skill"
    grep -F "_bmad/bmm/agents/analyst.agent.yaml" "$skill"
    grep -F "_bmad/core/tasks/bmad-create-prd/workflow.md" "$skill"
    grep -F "_bmad/bmm/workflows/3-solutioning/bmad-create-architecture/workflow.md" "$skill"
    grep -F "_bmad/bmm/workflows/3-solutioning/bmad-create-epics-and-stories/workflow.md" "$skill"
    grep -F "_bmad/bmm/workflows/3-solutioning/bmad-check-implementation-readiness/workflow.md" "$skill"
    grep -F "Subagent Execution Log" "$skill"
    grep -F "Use \`1\` to \`3\` validation rounds per artifact." "$skill"
  '
  assert_success
}

@test "autonomous planning docs use prompt-based triggers instead of launcher commands" {
  run bash -lc '
    set -euo pipefail
    guide=".claude/skills/AI-AGENT-GUIDE.md"
    readme=".claude/skills/README.md"
    decision=".claude/skills/SKILL-DECISION-GUIDE.md"
    repo_readme="README.md"
    onboarding="docs/onboarding.md"
    getting_started="docs/getting-started.md"
    agents_doc="AGENTS.md"

    grep -F "Preferred Codex trigger for this skill:" "$guide"
    grep -F "Use the bmad-autonomous-planning skill to plan a new feature." "$guide"
    grep -F "**Key trigger prompt**" "$readme"
    grep -F "run the flow in the current session" "$decision"
    grep -F "bmad-autonomous-planning" "$repo_readme"
    grep -F "current AI" "$repo_readme"
    grep -F ".claude/skills/bmad-autonomous-planning/SKILL.md" "$repo_readme"
    grep -F "\`bmad-autonomous-planning\` skill from the current AI agent session" "$onboarding"
    grep -F ".claude/skills/bmad-autonomous-planning/SKILL.md" "$onboarding"
    grep -F "autonomous BMALPH planner" "$getting_started"
    grep -F ".claude/skills/bmad-autonomous-planning/SKILL.md" "$getting_started"
    grep -F "without relying on repo-local launchers" "$agents_doc"
    ! rg -n "make bmalph-autonomous-plan|run-autonomous-bmad-planning|PLAN_TASK=|PLAN_DEBUG=|PLAN_DRY_RUN=|PLAN_RESULT_FILE=" \
      "$guide" "$readme" "$decision" "$repo_readme" "$onboarding" "$getting_started" "$agents_doc"
  '
  assert_success
}
