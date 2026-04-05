#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "make phpcsfixer command executes" {
  run make phpcsfixer
  assert_success
  assert_output --partial "Running analysis on "
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
    ./vendor/bin/psalm --clear-cache >/dev/null

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
  assert_success
}
