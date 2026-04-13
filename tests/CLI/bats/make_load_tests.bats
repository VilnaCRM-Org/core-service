#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "make smoke-load-tests command executes" {
  skip "Requires Docker - skipped in CI environment"
}

@test "make average-load-tests command executes" {
  skip "Requires Docker - skipped in CI environment"
}

@test "make stress-load-tests command executes" {
  skip "Requires Docker - skipped in CI environment"
}

@test "make spike-load-tests command executes" {
  skip "Requires Docker - skipped in CI environment"
}

@test "make load-tests command runs all load test scenarios" {
  skip "Requires Docker - skipped in CI environment"
}

@test "make prepare-test-data command prepares data" {
  skip "Requires Docker - skipped in CI environment"
}

@test "make cleanup-test-data command cleans up data" {
  skip "Requires Docker - skipped in CI environment"
}

@test "make build-k6-docker builds k6 image" {
  skip "Requires Docker - skipped in CI environment"
}

@test "make build-k6-docker builds k6 image directly" {
  run sed -n '/^build-k6-docker:/,/^build-spectral-docker:/p' Makefile
  assert_success
  assert_output --partial 'build-k6-docker:'
  assert_output --partial '$(DOCKER) build -t k6 -f ./tests/Load/Dockerfile .'
}

@test "worker-mode verification target runs repeated smoke tests with a memory guardrail" {
  run sed -n '/^worker-mode-verification:/,/^prepare-test-data:/p' Makefile
  assert_success
  assert_output --partial 'worker-mode-verification: memory-tests build-k6-docker'
  assert_output --partial 'verify-frankenphp-worker-memory.sh'
  assert_output --partial 'SOAK_ITERATIONS'
  assert_output --partial 'WORKER_MEMORY_ALLOWED_GROWTH_MIB'
  assert_output --partial 'WORKER_MEMORY_SERVICE'
}

@test "worker memory verification disables K6 latency thresholds but keeps smoke traffic" {
  run sed -n '1,220p' tests/Load/verify-frankenphp-worker-memory.sh
  assert_success
  assert_output --partial 'K6_SKIP_DURATION_THRESHOLDS="${K6_SKIP_DURATION_THRESHOLDS:-1}"'
  assert_output --partial 'make smoke-load-tests-no-build'
}

@test "load-test scripts use configurable base domains instead of hardcoded localhost:80" {
  run grep -n 'localhost:80' \
    tests/Load/scripts/rest-api/getCustomerStatus.js \
    tests/Load/scripts/rest-api/updateCustomerStatus.js \
    tests/Load/scripts/rest-api/updateCustomerType.js
  assert_equal "$status" "1"
}

@test "make execute-load-tests-script with scenario parameter" {
  skip "Requires Docker - skipped in CI environment"
}

@test "make start-prod-loadtest starts production environment" {
  skip "Requires Docker - skipped in CI environment"
}

@test "make stop-prod-loadtest stops production environment" {
  skip "Requires Docker - skipped in CI environment"
}
