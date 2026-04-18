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
  assert_output --partial 'worker-mode-verification: ## Run repeated smoke load tests against the running FrankenPHP worker mode stack'
  assert_output --partial 'verify-frankenphp-worker-memory.sh'
  assert_output --partial 'default_load_test_port='
  assert_output --partial 'SOAK_ITERATIONS'
  assert_output --partial 'WORKER_MEMORY_ALLOWED_GROWTH_MIB'
  assert_output --partial 'WORKER_MEMORY_SERVICE'
}

@test "worker memory verification disables K6 latency thresholds but keeps smoke traffic" {
  run sed -n '1,220p' tests/Load/verify-frankenphp-worker-memory.sh
  assert_success
  assert_output --partial 'soak_scenarios=$(./tests/Load/get-load-test-scenarios.sh | paste -sd, -)'
  assert_output --partial 'LOAD_TEST_SCENARIOS="$soak_scenarios"'
  assert_output --partial 'K6_SKIP_DURATION_THRESHOLDS="${K6_SKIP_DURATION_THRESHOLDS:-1}"'
  assert_output --partial 'K6_SMOKE_RETRIES="${K6_SMOKE_RETRIES:-1}"'
  assert_output --partial 'make smoke-load-tests-no-build'
}

@test "worker memory verification uses a post-warmup baseline and only fails on sustained growth" {
  run sed -n '1,260p' tests/Load/verify-frankenphp-worker-memory.sh
  assert_success
  assert_output --partial 'cold_baseline_sample=$(measure_memory)'
  assert_output --partial 'run_soak_iteration "warmup"'
  assert_output --partial 'post_warmup_baseline_rss='
  assert_output --partial 'cold_to_final_delta_mib='
  assert_output --partial '[ "$monotonic_growth" = true ]'
  assert_output --partial 'treating this as warmup/transient growth'
}

@test "load-test scenario discovery supports explicit scenario overrides" {
  run sed -n '1,120p' tests/Load/get-load-test-scenarios.sh
  assert_success
  assert_output --partial 'SCENARIO_OVERRIDE=${LOAD_TEST_SCENARIOS:-}'
  assert_output --partial "sed -E 's/[[:space:],]+/\\n/g'"
  assert_output --partial 'missing_scenarios'
}

@test "load-test scenario discovery rejects invalid override scenarios early" {
  run env LOAD_TEST_SCENARIOS='health missing-scenario' bash tests/Load/get-load-test-scenarios.sh
  assert_failure
  assert_output --partial 'Error: Unknown load test scenario override(s):'
  assert_output --partial './tests/Load/scripts/missing-scenario.js'
}

@test "load-test scripts use configurable base domains instead of hardcoded localhost:80" {
  run grep -n 'localhost:80' \
    tests/Load/scripts/rest-api/getCustomerStatus.js \
    tests/Load/scripts/rest-api/updateCustomerStatus.js \
    tests/Load/scripts/rest-api/updateCustomerType.js
  assert_equal "$status" "1"
}

@test "execute-load-test forwards the memory-soak threshold override into k6" {
  run sed -n '1,120p' tests/Load/execute-load-test.sh
  assert_success
  assert_output --partial '-e "K6_SKIP_DURATION_THRESHOLDS=${K6_SKIP_DURATION_THRESHOLDS:-}"'
  assert_output --partial '-e "LOAD_TEST_API_SCHEME=${LOAD_TEST_API_SCHEME:-https}"'
}

@test "customer dependency bootstrap tolerates empty collection responses in worker-mode smoke setup" {
  run sed -n '1,220p' tests/Load/utils/insertCustomersUtils.js
  assert_success
  assert_output --partial 'parseCollectionResponse(response, resourceName)'
  assert_output --partial 'Received an empty ${resourceName} collection response'
  assert_output --partial 'Falling back to seed creation.'
}

@test "customer dependency bootstrap fetches full collections with JSON-LD accept headers" {
  run cat tests/Load/utils/utils.js
  assert_success
  assert_output --partial "Accept: 'application/ld+json'"
  assert_output --partial 'customer_types?itemsPerPage=100'
  assert_output --partial 'customer_statuses?itemsPerPage=100'
  assert_output --partial "config.apiScheme || 'http'"
}

@test "worker-mode verification targets the HTTPS listener used by official FrankenPHP Docker" {
  run sed -n '/^worker-mode-verification:/,/^export-memory-coverage:/p' Makefile
  assert_success
  assert_output --partial 'LOAD_TEST_API_SCHEME="$${LOAD_TEST_API_SCHEME:-https}"'
  assert_output --partial 'if [ "$${LOAD_TEST_API_SCHEME:-https}" = "http" ]'
  assert_output --partial 'LOAD_TEST_API_PORT="$${LOAD_TEST_API_PORT:-$$default_load_test_port}"'
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
