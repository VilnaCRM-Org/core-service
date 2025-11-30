#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "make ci command runs all CI checks" {
  run make ci
  assert_success
  assert_output --partial "✅ CI checks successfully passed!"
}

@test "make ci should fail if any check fails" {
  # Create a temporary file with syntax error
  echo "<?php syntax error here" > temp_bad_syntax.php

  run make ci

  rm -f temp_bad_syntax.php

  assert_failure
  assert_output --partial "❌ CI checks failed"
}

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

