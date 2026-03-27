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
@test "make infection should fail when mutation score is below threshold" {
  skip "Error detection may vary in CI environment"
}

@test "make behat should fail when scenarios fail" {
  skip "Test relies on environment-specific behavior"
}

@test "make psalm should fail when static analysis errors are present" {
  run bash -lc '
    set -e
    cd /workspaces/core-service
    target=src/Shared/Application/PsalmErrorExample.php
    cp tests/CLI/bats/php/PsalmErrorExample.php "$target"
    trap '\''rm -f "$target"; docker compose exec -T -e APP_ENV=test php ./vendor/bin/psalm --clear-cache >/dev/null 2>&1 || true'\'' EXIT
    docker compose exec -T -e APP_ENV=test php ./vendor/bin/psalm --clear-cache >/dev/null
    make psalm
  '
  assert_failure
  assert_output --partial "does not exist"
}

@test "make phpinsights should fail when code quality is low" {
  run bash -lc '
    set -e
    cd /workspaces/core-service
    target=src/temp_bad_code.php
    cp tests/CLI/bats/php/temp_bad_code.php "$target"
    trap '\''rm -f "$target"'\'' EXIT
    make phpinsights
  '
  assert_failure
  assert_output --partial "Cyclomatic Complexity of 10"
}

@test "phpunit should fail if tests fail" {
  run bash -lc '
    set -e
    cd /workspaces/core-service
    target=tests/Unit/FailingTest.php
    cp tests/CLI/bats/php/FailingTest.php "$target"
    trap '\''rm -f "$target"'\'' EXIT
    make unit-tests
  '
  assert_failure
  assert_output --partial "FAILURES!"
}

@test "PHP CS Fixer should report violations if present" {
  run bash -lc '
    set -e
    cd /workspaces/core-service
    target=temp_file.php
    printf "<?php \$foo = '\''bar'\'' ;\n" > "$target"
    trap '\''rm -f "$target"'\'' EXIT
    docker compose exec -T php ./vendor/bin/php-cs-fixer fix "$target" --dry-run --diff
  '
  assert_failure
  assert_output --partial "--- Original"
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
  assert_output --partial "composer.json"
}
