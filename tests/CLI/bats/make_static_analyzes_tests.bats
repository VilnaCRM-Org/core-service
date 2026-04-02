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
  run sed -n '/Start Application/,/Run Deptrac/p' .github/workflows/deptrac.yml
  assert_success
  assert_output --partial 'run: make start'
  assert_output --partial 'run: make deptrac'
  refute_output --partial 'docker compose up --detach --wait php'
}

@test "psalm workflow uses Makefile startup and analysis entrypoints" {
  run sed -n '/Start application services/,/Upload Security Analysis results to GitHub/p' .github/workflows/psalm.yml
  assert_success
  assert_output --partial 'run: make start'
  assert_output --partial 'run: make psalm'
  assert_output --partial 'run: make psalm-security-report'
  refute_output --partial 'docker compose up --detach --wait php'
}

@test "phpinsights workflow uses Makefile startup and analysis entrypoints" {
  run sed -n '/Start application services/,$p' .github/workflows/phpinsights.yml
  assert_success
  assert_output --partial 'run: make start'
  assert_output --partial 'run: CI=1 make phpinsights'
  refute_output --partial 'docker compose up --detach --wait php'
}
