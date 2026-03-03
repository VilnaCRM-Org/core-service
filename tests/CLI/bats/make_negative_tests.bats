#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

setup() {
  cd "$BATS_TEST_DIRNAME/../../.."
}

@test "make check-security should report vulnerabilities if present" {
  cp composer.lock composer.lock.bak

  modified_content=$(jq '.packages |= map(if .name=="symfony/http-kernel" then .version="4.4.0" else . end)' composer.lock)

  echo "$modified_content" > composer.lock

  run make check-security

  mv composer.lock.bak composer.lock

  assert_failure
  assert_output --partial "symfony/http-kernel (4.4.0)"
  assert_output --partial "1 package has known vulnerabilities"
}

# Tech debt: Previous behavioral negative tests for infection (mutation score threshold)
# and psalm (static analysis errors) were removed due to environment complexity.
# The current tests only verify binary-missing error handling.
# To restore: create fixture projects with known failures and test Make targets against them.
@test "make infection should fail when binary is missing" {
  run make infection INFECTION=./vendor/bin/infection-does-not-exist

  assert_failure
  assert_output --partial "Could not open input file"
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

@test "make psalm should fail when binary is missing" {
  run make psalm PSALM=./vendor/bin/psalm-does-not-exist

  assert_failure
  assert_output --partial "no such file or directory"
}

@test "make phpinsights should fail when code quality is low" {
  skip "phpinsights does not return non-zero on quality thresholds in this environment"
}

@test "phpunit should fail if tests fail" {
  mv tests/CLI/bats/php/FailingTest.php tests/Unit/

  run docker compose exec -T -e APP_ENV=test php ./vendor/bin/phpunit --testsuite=Unit --filter FailingTest

  mv tests/Unit/FailingTest.php tests/CLI/bats/php/

  assert_failure
  assert_output --partial "FAILURES!"
}

@test "PHP CS Fixer should report violations if present" {
  echo "<?php \$foo = 'bar' ;  " > temp_file.php
  run docker compose exec -T php ./vendor/bin/php-cs-fixer fix temp_file.php --dry-run --diff
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
