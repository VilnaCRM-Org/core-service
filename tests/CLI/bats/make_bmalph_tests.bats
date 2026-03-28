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

  before_status="$(git status --short --untracked-files=no)"

  run make bmalph-init BMALPH_PLATFORM=codex BMALPH_DRY_RUN=true
  assert_success
  assert_output --partial "[dry-run] Would perform the following actions:"
  assert_output --partial "Running BMALPH init in"
  assert_output --partial "No changes made."

  after_status="$(git status --short --untracked-files=no)"
  [ "${before_status}" = "${after_status}" ]
}

@test "make bmalph-setup supports one-command dry-run without changing tracked files" {
  local before_status after_status

  before_status="$(git status --short --untracked-files=no)"

  run make bmalph-setup BMALPH_PLATFORM=codex BMALPH_DRY_RUN=true
  assert_success
  assert_output --partial 'install-bmalph.sh --platform "codex" --init --dry-run'
  assert_output --partial "[dry-run] Would perform the following actions:"
  assert_output --partial "No changes made."

  after_status="$(git status --short --untracked-files=no)"
  [ "${before_status}" = "${after_status}" ]
}
