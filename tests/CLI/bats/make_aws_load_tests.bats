#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "make aws-load-tests works correctly" {
  if ! command -v aws >/dev/null 2>&1; then
    skip "AWS CLI not available - skipped"
  fi
  run make aws-load-tests LOCAL_MODE_ENV=true
  assert_output --partial "Launched instance"
  assert_output --partial "You can access the S3 bucket here"
  assert_success
}
