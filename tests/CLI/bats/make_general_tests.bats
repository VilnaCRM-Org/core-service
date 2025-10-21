#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "make help command lists all available targets" {
  run make help
  assert_success
  assert_output --partial "Usage:"
  assert_output --partial "make [target] [arg=\"val\"...]"
  assert_output --partial "Targets:"
}

@test "make composer-validate command executes and reports validity with warnings" {
  run bash -c "CI=1 make composer-validate"
  assert_success
  assert_output --partial "./composer.json is valid"
}

@test "make check-requirements command executes and passes" {
  run bash -c "CI=1 make check-requirements"
  assert_success
  assert_output --partial "Symfony Requirements Checker"
  assert_output --partial "Your system is ready to run Symfony projects"
}

@test "make phpinsights command executes and completes analysis" {
  run bash -c "CI=1 make phpinsights"
  assert_success
  assert_output --partial '✨ Analysis Completed !'
  assert_output --partial './vendor/bin/phpinsights --no-interaction --ansi --format=github-action'
}

@test "make check-security command executes and reports no vulnerabilities" {
  run bash -c "CI=1 make check-security"
  assert_success
  assert_output --partial "Symfony Security Check Report"
  assert_output --partial "No packages have known vulnerabilities."
}

@test "make infection command executes" {
  run bash -c "CI=1 make infection"
  assert_success
  assert_output --partial 'Infection - PHP Mutation Testing Framework'
  assert_output --partial 'Mutation Code Coverage: 100%'
}

@test "make execute-load-tests-script command executes" {
  skip "Requires Docker to build k6 image - skipped in CI environment"
}

@test "make cache-clear command executes" {
  run bash -c "CI=1 make cache-clear"
  assert_success
}

@test "make install command executes" {
  run bash -c "CI=1 make install"
  assert_success
}

@test "make update command executes" {
  run bash -c "CI=1 make update"
  assert_success
}

@test "make cache-warmup command executes" {
  run bash -c "CI=1 make cache-warmup"
  assert_success
}

@test "make purge command executes" {
  run make purge
  assert_success
}

@test "make logs shows docker logs" {
  skip "Requires Docker - skipped in CI environment"
}

@test "make new-logs command executes" {
  skip "Requires Docker - skipped in CI environment"
}

@test "make commands lists all available Symfony commands" {
  run bash -c "CI=1 make commands"
  assert_success
  assert_output --partial "Usage:"
  assert_output --partial "command [options] [arguments]"
  assert_output --partial "Options:"
  assert_output --partial "-h, --help            Display help for the given command."
  assert_output --partial "Available commands:"
}

@test "make coverage-html command executes" {
  skip "Requires Docker - skipped in CI environment"
}

@test "make generate-openapi-spec command executes" {
  run bash -c "CI=1 make generate-openapi-spec"
  assert_success
  openapi_file=".github/openapi-spec/spec.yaml"
  [ -f "$openapi_file" ]
  assert_success
}
