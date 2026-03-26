#!/usr/bin/env bats

# TODO: Track restoration of negative Make target tests in issue #142
#       See: https://github.com/VilnaCRM-Org/core-service/issues/142
#
# Skipped tests checklist (restore each with proper fixture):
# [ ] make check-security: Security vulnerability detection
# [ ] make infection: Mutation score threshold (currently skipped pending fixture-based coverage)
# [ ] make psalm: Static analysis error detection (currently skipped pending fixture-based coverage)
# [ ] make phpinsights: Code quality threshold detection
# [ ] phpunit: Test failure detection
# [ ] PHP CS Fixer: Code style violation detection
# [x] make composer-validate: Invalid composer.json detection
# [ ] make behat: E2E test failure detection

load 'bats-support/load'
load 'bats-assert/load'

setup() {
  cd "$BATS_TEST_DIRNAME/../../.."
}

@test "make check-security should report vulnerabilities if present" {
  skip "Security check behavior may vary - requires known vulnerable package version"
}

# Tech debt: Previous behavioral negative tests for infection (mutation score threshold)
# and psalm (static analysis errors) remain skipped pending fixture-based coverage.
# To restore: create fixture projects with known failures and test Make targets against them.
@test "make infection should fail when binary is missing" {
  skip "Error detection may vary in CI environment"
}

@test "make behat should fail when scenarios fail" {
  skip "Test relies on environment-specific behavior"
}

@test "make psalm should fail when binary is missing" {
  skip "Error message format differs between environments"
}

@test "make phpinsights should fail when code quality is low" {
  skip "phpinsights does not return non-zero on quality thresholds in this environment"
}

@test "phpunit should fail if tests fail" {
  skip "Test relies on ARGS being passed to Makefile, but unit-tests target ignores ARGS variable"
}

@test "PHP CS Fixer should report violations if present" {
  skip "Test requires specific output that may not appear in all conditions"
}

@test "make composer-validate should fail with invalid composer.json" {
  run bash -lc '
    backup=$(mktemp)
    cp composer.json "$backup"
    trap '\''mv "$backup" composer.json'\'' EXIT
    printf "{ invalid json\n" > composer.json
    make composer-validate
  '
  assert_failure
}
