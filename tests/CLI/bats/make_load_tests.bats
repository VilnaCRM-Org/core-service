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

@test "make build-k6-docker retries transient docker build failures" {
  tmpdir="$(mktemp -d)"
  counter_file="$tmpdir/docker-call-count"
  mock_docker="$tmpdir/docker"

  cat > "$mock_docker" <<'EOF'
#!/usr/bin/env bash
set -euo pipefail
counter_file="${MOCK_DOCKER_COUNTER_FILE:?}"
count=0
if [ -f "$counter_file" ]; then
  count="$(cat "$counter_file")"
fi
count=$((count + 1))
printf '%s' "$count" > "$counter_file"

if [ "$count" -lt 2 ]; then
  echo "transient docker hub failure" >&2
  exit 1
fi

exit 0
EOF
  chmod +x "$mock_docker"

  run env \
    MOCK_DOCKER_COUNTER_FILE="$counter_file" \
    PATH="$tmpdir:$PATH" \
    make build-k6-docker K6_DOCKER_BUILD_RETRIES=2 K6_DOCKER_BUILD_RETRY_DELAY_SECONDS=0

  call_count="$(cat "$counter_file")"
  rm -rf "$tmpdir"

  assert_success
  assert_output --partial "K6 Docker image build failed on attempt 1/2. Retrying in 0 seconds..."
  assert_equal "$call_count" "2"
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
