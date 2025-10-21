#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "make phpcsfixer command executes" {
  run bash -c "CI=1 make phpcsfixer"
  assert_success
  assert_output --partial "Running analysis on 1 core sequentially."
}

@test "make psalm command executes and reports no errors" {
  run bash -c "CI=1 make psalm"
  assert_success
  assert_output --partial 'No errors found!'
}

@test "make psalm-security command executes and reports no errors" {
  run bash -c "CI=1 make psalm-security"
  assert_success
  assert_output --partial 'No errors found!'
  assert_output --partial './vendor/bin/psalm --taint-analysis'
}

@test "make deptrac command executes and reports no violations" {
  run bash -c "CI=1 make deptrac"
  assert_output --partial './vendor/bin/deptrac analyse'
  assert_success
}

@test "make deptrac-debug command executes" {
  run bash -c "CI=1 make deptrac-debug"
  assert_output --partial 'App'
  assert_success
}
