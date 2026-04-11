#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "make phpcsfixer command executes" {
  run make phpcsfixer
  assert_success
  assert_output --partial "Running analysis"
}

@test "make psalm command executes and reports no errors" {
  run make psalm
  assert_success
  assert_output --partial 'No errors found!'
}

@test "make psalm allows concrete exception instantiation in src" {
  run bash -lc '
    set -euo pipefail
    source_path="tests/CLI/bats/php/SourcePatternGuardAllowedExceptionExample.php"
    target_path="src/Shared/Application/SourcePatternGuardAllowedExceptionExample.php"

    cleanup() {
      if [ -f "$target_path" ]; then
        mv "$target_path" "$source_path"
      fi
    }
    trap cleanup EXIT

    mv "$source_path" "$target_path"
    docker compose exec php php ./vendor/bin/psalm --clear-cache >/dev/null

    make psalm
  '

  assert_success
  assert_output --partial 'No errors found!'
}

@test "make psalm-security command executes and reports no errors" {
  run make psalm-security
  assert_success
  assert_output --partial 'No errors found!'
  assert_output --partial './vendor/bin/psalm --taint-analysis'
}

@test "make deptrac command executes and reports no violations" {
  run make deptrac
  assert_output --partial './vendor/bin/deptrac analyse'
  assert_success
}

@test "make deptrac-debug command executes" {
  run make deptrac-debug
  assert_output --partial 'App'
  # deptrac debug:unassigned may return 2 when it finds uncovered files.
  [[ "$status" -eq 0 || "$status" -eq 2 ]]
}

@test "deptrac workflow uses Makefile startup and deptrac entrypoints" {
  run sed -n '/Start Application/,/run: make deptrac/p' .github/workflows/deptrac.yml
  assert_success
  assert_output --partial 'run: make start'
  assert_output --partial 'run: make deptrac'
  refute_output --partial 'docker compose up --detach --wait php'
  refute_output --partial 'vendor/bin/deptrac'
}

@test "psalm workflow uses Makefile startup and analysis entrypoints" {
  run sed -n '/Start application services/,/Upload Security Analysis results to GitHub/p' .github/workflows/psalm.yml
  assert_success
  assert_output --partial 'run: make start'
  assert_output --partial 'run: make psalm'
  assert_output --partial 'run: make psalm-security-report'
  refute_output --partial 'docker compose up --detach --wait php'
  refute_output --partial 'vendor/bin/psalm'
}

@test "phpinsights workflow uses Makefile startup and analysis entrypoints" {
  run sed -n '/Start application services/,$p' .github/workflows/phpinsights.yml
  assert_success
  assert_output --partial 'run: make start'
  assert_output --partial 'run: CI=1 make phpinsights'
  refute_output --partial 'docker compose up --detach --wait php'
  refute_output --partial 'vendor/bin/phpinsights'
}

@test "phpinsights workflow configures the shared PHP version before host analysis" {
  run sed -n '1,/Start application services/p' .github/workflows/phpinsights.yml
  assert_success
  assert_output --partial 'shivammathur/setup-php@v2'
  assert_output --partial 'php-version: ${{ vars.PHP_VERSION }}'
}
