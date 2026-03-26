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

@test "make composer-validate command executes" {
  run make composer-validate
  assert_success
  assert_output --partial "composer validate"
}

@test "make check-requirements command is invoked" {
  run make check-requirements
  assert_success
  assert_output --partial "symfony check:requirements"
}

@test "make phpinsights command executes and completes analysis" {
  run make phpinsights
  assert_success
  assert_output --partial "./vendor/bin/phpmd"
  assert_output --partial "./vendor/bin/phpinsights"
}

@test "make phpinsights propagates CLI overrides" {
  run env PHPINSIGHTS_ARGS="--memory-limit=256M" make phpinsights
  assert_success
  assert_output --partial -- "--memory-limit=256M"
}

@test "make check-security command executes" {
  run make check-security
  assert_success
  assert_output --partial "symfony security:check"
}

@test "make infection command executes" {
  run make infection
  assert_success
  assert_output --partial "./vendor/bin/infection"
}

@test "make infection propagates threshold overrides" {
  run env INFECTION_MIN_MSI="99" INFECTION_MIN_COVERED_MSI="98" make infection
  assert_success
  assert_output --partial -- "--min-msi=99"
  assert_output --partial -- "--min-covered-msi=98"
}

@test "make execute-load-tests-script command executes" {
  skip "Requires Docker to build k6 image - skipped in CI environment"
}

@test "make cache-clear command executes" {
  run bash -c "CI=1 make cache-clear"
  run make cache-clear
}

@test "make install command executes" {
  run bash -c "CI=1 make install"
  run make install
}

@test "make update command executes" {
  run bash -c "CI=1 make update"
  run make update
}

@test "make cache-warmup command executes" {
  run bash -c "CI=1 make cache-warmup"
  run make cache-warmup
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
  assert_output --partial "Available commands"
  run make commands
  assert_success
  assert_output --partial "Available commands"
}

@test "make coverage-html command executes" {
  skip "Requires Docker - skipped in CI environment"
}

@test "make generate-openapi-spec command executes" {
  run bash -c "CI=1 make generate-openapi-spec"
  run make generate-openapi-spec
  openapi_file=".github/openapi-spec/spec.yaml"
  [ -f "$openapi_file" ]
  assert_success
}

@test "make ensure-test-services uses docker compose wait mode" {
  run sed -n '/^ensure-test-services:/,/^setup-test-db:/p' Makefile
  assert_success
  assert_output --partial 'ensure-test-services'
  assert_output --partial 'up --detach --wait database redis php caddy localstack'
}

@test "load test LocalStack healthcheck waits for SQS readiness" {
  run sed -n '/^  localstack:/,/^  redis:/p' docker-compose.load_test.override.yml
  assert_success
  assert_output --partial 'curl -sk https://localhost:4566/_localstack/health'
  assert_output --partial "grep -Eq ''\"sqs\":[[:space:]]*\"available\"''"
}
