#!/usr/bin/env bats

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
  assert_output --partial "symfony/http-kernel (v4.4.0)"
  assert_output --partial "1 package has known vulnerabilities"
}

@test "make infection should fail due to partly covered class" {
  run bash -lc '
    set -euo pipefail
    source_path="tests/CLI/bats/php/PartlyCoveredEventBus.php"
    target_path="src/Shared/Infrastructure/Bus/Event/PartlyCoveredEventBus.php"
    test_source_path="tests/CLI/bats/php/PartlyCoveredEventBusTest.php"
    test_target_path="tests/Unit/Shared/Infrastructure/Bus/Event/PartlyCoveredEventBusTest.php"

    cleanup() {
      if [ -f "$target_path" ]; then
        mv "$target_path" "$source_path"
      fi
      if [ -f "$test_target_path" ]; then
        mv "$test_target_path" "$test_source_path"
      fi
    }
    trap cleanup EXIT

    mv "$source_path" "$target_path"
    mv "$test_source_path" "$test_target_path"
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
  assert_output --partial "1 covered mutants were not detected"
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
    docker compose exec php composer dump-autoload >/dev/null
    docker compose exec -e APP_ENV=test php ./vendor/bin/psalm --clear-cache >/dev/null

    set +e
    make psalm
    status=$?
    set -e

    exit "$status"
  '

  assert_failure
  assert_output --partial "NonExistentTrait"
}

@test "make source-pattern-guard should fail on non-baselined violations" {
  run bash -lc '
    set -euo pipefail
    source_path="tests/CLI/bats/php/SourcePatternGuardExample.php"
    target_path="src/Shared/Application/SourcePatternGuardExample.php"

    cleanup() {
      if [ -f "$target_path" ]; then
        mv "$target_path" "$source_path"
      fi
    }
    trap cleanup EXIT

    mv "$source_path" "$target_path"

    set +e
    make source-pattern-guard
    status=$?
    set -e

    exit "$status"
  '

  assert_failure
  assert_output --partial "Source pattern guard found non-baselined violations:"
  assert_output --partial "Hardcoded new expression found"
}

@test "make source-pattern-guard should fail on typed class constants with array type declarations" {
  run bash -lc '
    set -euo pipefail
    source_path="tests/CLI/bats/php/SourcePatternGuardTypedConstExample.php"
    target_path="src/Shared/Application/SourcePatternGuardTypedConstExample.php"

    cleanup() {
      if [ -f "$target_path" ]; then
        mv "$target_path" "$source_path"
      fi
    }
    trap cleanup EXIT

    mv "$source_path" "$target_path"

    set +e
    make source-pattern-guard
    status=$?
    set -e

    exit "$status"
  '

  assert_failure
  assert_output --partial "Source pattern guard found non-baselined violations:"
  assert_output --partial "array_type_declaration"
}

@test "make source-pattern-guard baseline generation should fail on parse errors" {
  run bash -lc '
    set -euo pipefail
    source_path="tests/CLI/bats/php/SourcePatternGuardParseErrorExample.php"
    target_path="src/Shared/Application/SourcePatternGuardParseErrorExample.php"
    baseline_path="config/static-analysis/source-pattern-baseline.json"
    baseline_backup=""
    baseline_exists=0

    if [ -f "$baseline_path" ]; then
      baseline_exists=1
      baseline_backup="$(mktemp)"
      cp "$baseline_path" "$baseline_backup"
    fi

    cleanup() {
      if [ -f "$target_path" ]; then
        mv "$target_path" "$source_path"
      fi
      if [ "$baseline_exists" -eq 1 ] && [ -n "$baseline_backup" ] && [ -f "$baseline_backup" ]; then
        cp "$baseline_backup" "$baseline_path"
        rm -f "$baseline_backup"
      fi
      if [ "$baseline_exists" -eq 0 ] && [ -f "$baseline_path" ]; then
        rm -f "$baseline_path"
      fi
    }
    trap cleanup EXIT

    mv "$source_path" "$target_path"
    make ensure-test-services >/dev/null

    set +e
    docker compose exec php php -d display_errors=0 -d error_reporting='"'"'E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED'"'"' scripts/guard-source-patterns.php --generate-baseline
    status=$?
    set -e

    exit "$status"
  '

  assert_failure
  assert_output --partial "Refusing to generate a baseline while some files cannot be analyzed."
  assert_output --partial "[parse_error]"
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
  assert_output --partial "The method anotherBadMethod() has a Cyclomatic Complexity of 10"
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

    echo "<?php \$foo = '"'"'\'"'"''"'"'bar'"'"'\'"'"''"'"' ;  " > temp_file.php

    set +e
    docker compose exec php ./vendor/bin/php-cs-fixer fix temp_file.php --dry-run --diff
    status=$?
    set -e

    exit "$status"
  '

  assert_failure
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
}
