#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "make coverage-html command generates HTML coverage report" {
  skip "Requires Docker - skipped in CI environment"
}

@test "make coverage-xml command generates XML coverage report" {
  skip "Requires Docker - skipped in CI environment"
}

@test "make unit-tests command requires 100% coverage" {
  run make unit-tests
  assert_success
  assert_output --partial "COVERAGE SUCCESS: Line coverage is 100.00%"
}

@test "make behat command runs Behat scenarios" {
  run make behat
  assert_success
}

@test "make integration-negative-tests command executes" {
  run make integration-negative-tests
  assert_success
}

@test "make negative-tests-with-coverage command executes" {
  run make negative-tests-with-coverage
  assert_success
}

