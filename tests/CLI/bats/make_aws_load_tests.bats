#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "make aws-load-tests works correctly" {
  if command -v aws >/dev/null 2>&1; then
    run make aws-load-tests LOCAL_MODE_ENV=true
    assert_output --partial "Launched instance"
    assert_output --partial "You can access the S3 bucket here"
    assert_success
    return
  fi

  run sed -n '/^aws-load-tests:/,/^aws-load-tests-cleanup:/p' Makefile
  assert_success
  assert_output --partial "tests/Load/aws-execute-load-tests.sh"
  assert [ -x "tests/Load/aws-execute-load-tests.sh" ]
}
