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
  run make composer-validate
  assert_success
  assert_output --partial "./composer.json is valid"
}

@test "make check-requirements command executes and passes" {
  run make check-requirements
  assert_success
  assert_output --partial "Symfony Requirements Checker"
  assert_output --partial "Your system is ready to run Symfony projects"
}

@test "make phpinsights command executes and completes analysis" {
  run make phpinsights
  assert_success
  assert_output --partial '✨ Analysis Completed !'
}

@test "make check-security command executes and reports no vulnerabilities" {
  run make check-security
  assert_success
  assert_output --partial "Symfony Security Check Report"
  assert_output --partial "No packages have known vulnerabilities."
}

@test "make infection command executes" {
  run make infection
  assert_success
  assert_output --partial 'Infection - PHP Mutation Testing Framework'
  assert_output --partial 'Mutation Code Coverage: 100%'
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
  run make commands
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
  run make generate-openapi-spec
  openapi_file=".github/openapi-spec/spec.yaml"
  [ -f "$openapi_file" ]
  assert_success
}

@test "make ensure-test-services uses docker compose wait mode" {
  run sed -n '/^ensure-test-services:/,/^setup-test-db:/p' Makefile
  assert_success
  assert_output --partial 'ensure-test-services'
  assert_output --partial 'DOCKER_COMPOSE_UP_RETRIES:-5'
  assert_output --partial 'DOCKER_COMPOSE_UP_RETRY_DELAY_SECONDS:-5'
  assert_output --partial 'up --detach --wait database redis php localstack'
}

@test "make start waits for required services before building k6" {
  run sed -n '/^.PHONY: start/,/^ps:/p' Makefile
  assert_success
  assert_output --partial 'start: ensure-test-services build-k6-docker'
  refute_output --partial 'start: up build-k6-docker'
}

@test "make uses conditional docker exec tty flag" {
  run sed -n '/^DOCKER_TTY_FLAG/,/^endef/p' Makefile
  assert_success
  assert_output --partial 'DOCKER_TTY_FLAG = $(if $(CI),-T,)'
  assert_output --partial '$(DOCKER_COMPOSE) exec $(DOCKER_TTY_FLAG) -e $(1) php $(2)'
}

@test "make build-spectral-docker builds spectral image directly" {
  run sed -n '/^build-spectral-docker:/,/^infection:/p' Makefile
  assert_success
  assert_output --partial 'build-spectral-docker:'
  assert_output --partial '$(DOCKER) build -t core-service-spectral -f ./docker/spectral/Dockerfile .'
}

@test "memory-tests workflow uses make-only FrankenPHP worker entrypoints" {
  run cat .github/workflows/memory-tests.yml
  assert_success
  assert_output --partial 'COMPOSE_FILE: docker-compose.yml:docker-compose.override.yml:docker-compose.load_test.override.yml'
  refute_output --partial 'composer install'
  refute_output --partial 'setup-php'
  refute_output --partial 'docker compose cp'

  run awk '/^[[:space:]]*run:[[:space:]]+make[[:space:]]+/ { sub(/^[[:space:]]*/, "", $0); print }' .github/workflows/memory-tests.yml
  assert_success
  assert_output $'run: make start\nrun: make worker-mode-verification\nrun: make export-memory-coverage\nrun: make down'
}

@test "load test LocalStack healthcheck waits for SQS readiness" {
  run awk '
    /^  localstack:/ {in_block=1}
    in_block && /^  [[:alnum:]_-]+:/ && $0 !~ /^  localstack:/ {exit}
    in_block {print}
  ' docker-compose.load_test.override.yml
  assert_success
  assert_output --partial 'curl -fsS http://localhost:4566/_localstack/health'
  assert_output --partial 'grep -Eq "\"sqs\": \"(available|running)\""'
}

@test "dev and load-test FrankenPHP overrides keep the official automatic HTTPS flow" {
  run grep -n 'CADDY_GLOBAL_OPTIONS: auto_https off' docker-compose.override.yml docker-compose.load_test.override.yml
  assert_equal "$status" "1"
}

@test "Behat targets the FrankenPHP HTTPS endpoint" {
  run grep -n 'base_url: "https://localhost"' behat.yml.dist
  assert_success
}
