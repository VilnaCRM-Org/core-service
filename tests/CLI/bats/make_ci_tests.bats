#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "make pr-comments requires PR number or auto-detection" {
  # Test without gh CLI will fail gracefully
  run make pr-comments

  # Should either work or show error about gh CLI
  if [ "$status" -ne 0 ]; then
    assert_output --partial "Auto-detecting PR"
  fi
}

@test "make pr-comments with PR number" {
  skip "Requires GitHub CLI and PR context"
}
