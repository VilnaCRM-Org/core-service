#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "make integration-tests command executes" {
  run make integration-tests
  assert_output --partial 'PHPUnit'
  assert_success
}

@test "make tests-with-coverage command executes" {
  run make tests-with-coverage
  assert_output --partial 'Code Coverage Report:'
  assert_success
}
