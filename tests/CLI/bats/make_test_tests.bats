#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "make integration-tests command executes" {
  run make integration-tests
  assert_output --partial './vendor/bin/phpunit --coverage-text --testsuite=Integration'
  assert_success
}

@test "make tests-with-coverage command executes" {
  run make tests-with-coverage
  assert_output --partial './vendor/bin/phpunit --coverage-text'
  assert_success
}
