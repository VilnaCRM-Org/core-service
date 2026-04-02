#!/usr/bin/env bats

# Negative test coverage checklist:
# [x] make check-security: Security vulnerability detection
# [x] make infection: Mutation score threshold
# [x] make psalm: Static analysis error detection
# [x] make phpinsights: Code quality threshold detection
# [x] make unit-tests: Test failure detection
# [x] PHP CS Fixer: Code style violation detection
# [x] make composer-validate: Invalid composer.json detection
# [x] make behat: E2E test failure detection

load 'bats-support/load'
load 'bats-assert/load'

@test "make check-security should report vulnerabilities if present" {
  run bash -lc '
    set -euo pipefail
    cleanup() {
      if [ -f composer.lock.bak ]; then
        mv composer.lock.bak composer.lock
      fi
    }
    trap cleanup EXIT

    cp composer.lock composer.lock.bak
    original_content=$(cat composer.lock)
    modified_content=$(echo "$original_content" | jq '"'"'.packages += [{"name": "symfony/http-kernel", "version": "v4.4.0"}]'"'"')
    echo "$modified_content" > composer.lock

    set +e
    make check-security
    status=$?
    set -e

    exit "$status"
  '

  assert_failure
  assert_output --partial "symfony/http-kernel"
  [[ "$output" =~ Found\ [0-9]+\ security\ vulnerability\ advisories\ affecting\ 1\ package ]]
}

@test "make infection should fail due to partly covered class" {
  run bash -lc '
    set -euo pipefail
    source_path="tests/CLI/bats/php/PartlyCoveredEventBus.php"
    target_path="src/Shared/Infrastructure/Bus/Event/PartlyCoveredEventBus.php"

    cleanup() {
      if [ -f "$target_path" ]; then
        mv "$target_path" "$source_path"
      fi
    }
    trap cleanup EXIT

    mv "$source_path" "$target_path"
    make ensure-test-services >/dev/null
    docker compose exec php composer dump-autoload >/dev/null
    make unit-tests >/dev/null 2>&1 || true

    set +e
    make infection
    status=$?
    set -e

    exit "$status"
  '

  assert_failure
  assert_output --partial "8 mutants were not covered by tests"
}

@test "make behat should fail when scenarios fail" {
  run bash -lc '
    set -euo pipefail
    original_path="tests/Behat/CustomerContext/CustomerContext.php"
    temp_path="tests/CustomerContext.php"

    cleanup() {
      if [ -f "$temp_path" ]; then
        mv "$temp_path" "$original_path"
      fi
    }
    trap cleanup EXIT

    mv "$original_path" "$temp_path"

    set +e
    make behat
    status=$?
    set -e

    exit "$status"
  '

  assert_failure
}

@test "make psalm should fail when there are errors" {
  run bash -lc '
    set -euo pipefail
    source_path="tests/CLI/bats/php/PsalmErrorExample.php"
    target_path="src/Shared/Application/PsalmErrorExample.php"

    cleanup() {
      if [ -f "$target_path" ]; then
        mv "$target_path" "$source_path"
      fi
    }
    trap cleanup EXIT

    mv "$source_path" "$target_path"
    make ensure-test-services >/dev/null
    docker compose exec -e APP_ENV=test php ./vendor/bin/psalm --clear-cache >/dev/null
    docker compose exec php composer dump-autoload >/dev/null

    set +e
    make psalm
    status=$?
    set -e

    exit "$status"
  '

  assert_failure
  [[ "$output" =~ does\ not\ exist|NonExistentTrait ]]
}

@test "make phpinsights should fail when code quality is low" {
  run bash -lc '
    set -euo pipefail
    source_path="tests/CLI/bats/php/temp_bad_code.php"
    target_path="src/temp_bad_code.php"

    cleanup() {
      if [ -f "$target_path" ]; then
        mv "$target_path" "$source_path"
      fi
    }
    trap cleanup EXIT

    mv "$source_path" "$target_path"

    set +e
    make phpinsights
    status=$?
    set -e

    exit "$status"
  '

  assert_failure
  assert_output --partial "Cyclomatic Complexity of 10"
}

@test "make unit-tests should fail if tests fail" {
  run bash -lc '
    set -euo pipefail
    source_path="tests/CLI/bats/php/FailingTest.php"
    target_path="tests/Unit/FailingTest.php"

    cleanup() {
      if [ -f "$target_path" ]; then
        mv "$target_path" "$source_path"
      fi
    }
    trap cleanup EXIT

    mv "$source_path" "$target_path"

    set +e
    make unit-tests
    status=$?
    set -e

    exit "$status"
  '

  assert_failure
  assert_output --partial "FAILURES!"
}

@test "PHP CS Fixer should report violations if present" {
  run bash -lc '
    set -euo pipefail
    cleanup() {
      rm -f temp_file.php
    }
    trap cleanup EXIT

    echo "<?php \$foo = '"'"'bar'"'"' ;  " > temp_file.php

    set +e
    docker compose exec -T php ./vendor/bin/php-cs-fixer fix temp_file.php --allow-risky=yes --dry-run --diff
    status=$?
    set -e

    exit "$status"
  '

  assert_failure
  assert_output --partial "begin diff"
}

@test "make composer-validate should fail with invalid composer.json" {
  run bash -lc '
    set -euo pipefail
    cleanup() {
      if [ -f composer.json.bak ]; then
        mv composer.json.bak composer.json
      fi
    }
    trap cleanup EXIT

    mv composer.json composer.json.bak
    echo "{" > composer.json

    set +e
    make composer-validate
    status=$?
    set -e

    exit "$status"
  '

  assert_failure
  assert_output --partial "composer.json"
}
