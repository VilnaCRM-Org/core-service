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
