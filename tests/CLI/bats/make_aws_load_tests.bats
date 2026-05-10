#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "load config preserves runtime AWS emulator port overrides in local mode" {
  run bash -lc '
    set -euo pipefail
    fake_bin=$(mktemp -d)
    trap "rm -rf \"$fake_bin\"" EXIT
    cat >"$fake_bin/docker" <<'"'"'EOF'"'"'
#!/bin/sh
exit 1
EOF
    chmod +x "$fake_bin/docker"
    export PATH="$fake_bin:$PATH"
    export AWS_SQS_KEY=test-key AWS_SQS_SECRET=test-secret AWS_EMULATOR_PORT=14566 LOCAL_MODE_ENV=true
    source tests/Load/config.sh >/dev/null
    [ "$ENDPOINT_URL" = "http://localhost:14566" ]
  '
  assert_success
}

@test "load config prefers the published AWS emulator host port in local mode" {
  run bash -lc '
    set -euo pipefail
    fake_bin=$(mktemp -d)
    trap "rm -rf \"$fake_bin\"" EXIT
    cat >"$fake_bin/docker" <<'"'"'EOF'"'"'
#!/bin/sh
if [ "$1" = "compose" ] && [ "$2" = "port" ] && [ "$3" = "aws-emulator" ] && [ "$4" = "4566" ]; then
  printf "%s\n" "0.0.0.0:32769"
  exit 0
fi
exit 1
EOF
    chmod +x "$fake_bin/docker"
    export PATH="$fake_bin:$PATH"
    export AWS_SQS_KEY=test-key AWS_SQS_SECRET=test-secret AWS_EMULATOR_PORT=14566 LOCAL_MODE_ENV=true
    source tests/Load/config.sh >/dev/null
    [ "$AWS_EMULATOR_PORT" = "32769" ]
    [ "$ENDPOINT_URL" = "http://localhost:32769" ]
  '
  assert_success
}

@test "make aws-load-tests bootstraps services in local mode" {
  run make -n aws-load-tests LOCAL_MODE_ENV=true
  assert_success
  assert_output --partial "ensure-test-services"
  assert_output --partial "tests/Load/aws-execute-load-tests.sh"
}

@test "make aws-emulator-smoke validates required local AWS APIs" {
  run make -n aws-emulator-smoke
  assert_success
  assert_output --partial "up --detach --wait database redis php aws-emulator"
  assert_output --partial "scripts/aws-emulator-smoke.sh"
}

@test "aws emulator compose services define AWS API readiness healthchecks" {
  run bash -lc '
    set -euo pipefail
    for file in docker-compose.override.yml docker-compose.load_test.override.yml; do
      block="$(awk "
        /^  aws-emulator:/ { in_block=1 }
        in_block && /^  [[:alnum:]_-]+:/ && \$0 !~ /^  aws-emulator:/ { exit }
        in_block { print }
      " "$file")"
      grep -F "healthcheck:" <<<"$block"
      grep -F "aws --endpoint-url=http://localhost:4566 sqs list-queues" <<<"$block"
    done
  '
  assert_success
}

@test "load-tests workflow waits for the AWS emulator through make start" {
  run cat .github/workflows/load-tests.yml
  assert_success
  assert_output --partial 'run: make start'
  assert_output --partial 'run: make smoke-load-tests'
  refute_output --partial 'setup-php'
  refute_output --partial 'composer install'
}

@test "cache-performance workflow waits for the AWS emulator through make start" {
  run cat .github/workflows/cache-performance-tests.yml
  assert_success
  assert_output --partial 'run: make start'
  assert_output --partial 'run: make cache-performance-load-tests'
  assert_output --partial 'run: make down'
  refute_output --partial 'setup-php'
  refute_output --partial 'composer install'
}

@test "make aws-load-tests works correctly" {
  if ! command -v aws >/dev/null 2>&1; then
    run sed -n '/^aws-load-tests:/,/^aws-load-tests-cleanup:/p' Makefile
    assert_success
    assert_output --partial "tests/Load/aws-execute-load-tests.sh"
    assert [ -x "tests/Load/aws-execute-load-tests.sh" ]
    return
  fi

  run bash -lc '
    set -euo pipefail
    cleanup() {
      make aws-load-tests-cleanup LOCAL_MODE_ENV=true >/dev/null 2>&1 || true
    }
    trap cleanup EXIT
    make aws-load-tests LOCAL_MODE_ENV=true
  '
  assert_output --partial "Launched instance"
  assert_output --partial "You can access the S3 bucket here"
  assert_success
}
