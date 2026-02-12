#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "make check-security should report vulnerabilities if present" {
  cp composer.lock composer.lock.bak

  original_content=$(cat composer.lock)

  modified_content=$(echo "$original_content" | jq '.packages += [{"name": "symfony/http-kernel", "version": "v4.4.0"}]')

  echo "$modified_content" > composer.lock

  run make check-security

  mv composer.lock.bak composer.lock

  assert_failure
  assert_output --partial "symfony/http-kernel (v4.4.0)"
  assert_output --partial "1 package has known vulnerabilities"
}

@test "make infection should fail due to partly covered class" {
  mv tests/CLI/bats/php/PartlyCoveredEventBus.php src/Shared/Infrastructure/Bus/Event/

  composer dump-autoload

  run make unit-tests
  run make infection

  mv src/Shared/Infrastructure/Bus/Event/PartlyCoveredEventBus.php tests/CLI/bats/php/

  assert_failure

  # PHP 8.4 may show "errors were encountered" instead of "mutants were not covered"
  if [[ ! "$output" =~ "mutants were not covered by tests" ]] && [[ ! "$output" =~ "errors were encountered" ]]; then
    echo "Expected either 'mutants were not covered by tests' or 'errors were encountered', but got neither"
    return 1
  fi
}

@test "make behat should fail when scenarios fail" {
  original_path="tests/Behat/CustomerContext/CustomerContext.php"
  temp_path="tests/CustomerContext.php"
  
  cleanup() {
    if [ -f "$temp_path" ]; then
      mv "$temp_path" "$original_path"
    fi
  }
  trap cleanup EXIT
  
  mv "$original_path" "$temp_path"
  run make behat
  
  mv "$temp_path" "$original_path"
  
  assert_failure
}

@test "make psalm should fail when there are errors" {
  mv tests/CLI/bats/php/PsalmErrorExample.php src/Shared/Application/

  # Regenerate autoloader and clear Psalm cache
  composer dump-autoload
  docker compose exec -e APP_ENV=test php ./vendor/bin/psalm --clear-cache

  run make psalm

  mv src/Shared/Application/PsalmErrorExample.php tests/CLI/bats/php/

  assert_failure
  assert_output --partial "does not exist"
}

@test "make phpinsights should fail when code quality is low" {
  mv tests/CLI/bats/php/temp_bad_code.php src/temp_bad_code.php

  run make phpinsights

  mv src/temp_bad_code.php tests/CLI/bats/php/

  assert_failure
  assert_output --partial "The method anotherBadMethod() has a Cyclomatic Complexity of 10"
}

@test "make unit-tests should fail if tests fail" {
  mv tests/CLI/bats/php/FailingTest.php tests/Unit/

  run make unit-tests

  mv tests/Unit/FailingTest.php tests/CLI/bats/php/

  assert_failure
  assert_output --partial "FAILURES!"
}

@test "PHP CS Fixer should report violations if present" {
  echo "<?php \$foo = 'bar' ;  " > temp_file.php
  run docker compose exec php ./vendor/bin/php-cs-fixer fix temp_file.php --dry-run --diff
  rm temp_file.php
  assert_failure
}

@test "make composer-validate should fail with invalid composer.json" {
  mv composer.json composer.json.bak
  echo "{" > composer.json

  run make composer-validate

  mv composer.json.bak composer.json

  assert_failure
}

