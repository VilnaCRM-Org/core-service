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

@test "E2E workflow uses make-only Docker entrypoints" {
  run cat .github/workflows/E2Etests.yml
  assert_success
  assert_output --partial 'run: make start'
  assert_output --partial 'run: make behat'
  assert_output --partial 'run: make down'
  refute_output --partial 'composer install'
  refute_output --partial 'setup-php'
}

@test "PHPUnit workflow uses make-only Docker entrypoints" {
  run cat .github/workflows/tests.yml
  assert_success
  assert_output --partial 'run: make start'
  assert_output --partial 'run: make coverage-xml'
  assert_output --partial 'files: coverage/coverage.xml'
  assert_output --partial 'run: make down'
  refute_output --partial 'composer install'
  refute_output --partial 'setup-php'
}
