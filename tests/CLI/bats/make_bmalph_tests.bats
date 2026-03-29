#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

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
  run make bmalph-install BMALPH_PLATFORM=codex
  assert_success
  assert_output --partial "BMALPH installed:"
  assert_output --partial "BMALPH dry-run verification passed for platform 'codex'."
  assert_output --partial "BMALPH CLI is ready."
}

@test "make bmalph-codex installs and verifies the Codex BMALPH flow" {
  run make bmalph-codex
  assert_success
  assert_output --partial 'install-bmalph.sh --platform "codex"'
  assert_output --partial "BMALPH dry-run verification passed for platform 'codex'."
}

@test "make bmalph-claude installs and verifies the Claude BMALPH flow" {
  run make bmalph-claude
  assert_success
  assert_output --partial 'install-bmalph.sh --platform "claude-code"'
  assert_output --partial "BMALPH dry-run verification passed for platform 'claude-code'."
}

@test "make bmalph-init supports dry-run without changing tracked files" {
  local before_status after_status

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
    ! rg -n "container_name:\\s*localstack" docker-compose.override.yml docker-compose.load_test.override.yml
    rg -n "\\$\\{REDIS_PORT:-6379\\}:6379" docker-compose.yml
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
    rg -n "\"initializeCommand\": \"bash \\.devcontainer/initialize\\.sh\"" .devcontainer/devcontainer.json
  '
  assert_success
}

@test "devcontainer keeps workspace path host-visible for docker-outside-of-docker" {
  run bash -lc '
    set -euo pipefail
    rg -n "\"workspaceMount\": \"source=\\$\\{localWorkspaceFolder\\},target=\\$\\{localWorkspaceFolder\\},type=bind\"" .devcontainer/devcontainer.json
    rg -n "\"workspaceFolder\": \"\\$\\{localWorkspaceFolder\\}\"" .devcontainer/devcontainer.json
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
    rg -n "^GH_GIT_PROTOCOL=\"\\$\\{GH_GIT_PROTOCOL:-https\\}\"$" "$repo_root/.devcontainer/workspace-settings.env"
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
