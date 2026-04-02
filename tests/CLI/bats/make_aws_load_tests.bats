#!/usr/bin/env bats

load 'bats-support/load'
load 'bats-assert/load'

@test "load config preserves runtime LocalStack port overrides in local mode" {
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
    export AWS_SQS_KEY=test-key AWS_SQS_SECRET=test-secret LOCALSTACK_PORT=14566 LOCAL_MODE_ENV=true
    source tests/Load/config.sh >/dev/null
    [ "$ENDPOINT_URL" = "http://localhost:14566" ]
  '
  assert_success
}

@test "load config prefers the published LocalStack host port in local mode" {
  run bash -lc '
    set -euo pipefail
    fake_bin=$(mktemp -d)
    trap "rm -rf \"$fake_bin\"" EXIT
    cat >"$fake_bin/docker" <<'"'"'EOF'"'"'
#!/bin/sh
if [ "$1" = "compose" ] && [ "$2" = "port" ] && [ "$3" = "localstack" ] && [ "$4" = "4566" ]; then
  printf "%s\n" "0.0.0.0:32769"
  exit 0
fi
exit 1
EOF
    chmod +x "$fake_bin/docker"
    export PATH="$fake_bin:$PATH"
    export AWS_SQS_KEY=test-key AWS_SQS_SECRET=test-secret LOCALSTACK_PORT=14566 LOCAL_MODE_ENV=true
    source tests/Load/config.sh >/dev/null
    [ "$LOCALSTACK_PORT" = "32769" ]
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

@test "localstack compose services define a readiness healthcheck" {
  run bash -lc '
    set -euo pipefail
    for file in docker-compose.override.yml docker-compose.load_test.override.yml; do
      block="$(awk "
        /^  localstack:/ { in_block=1 }
        in_block && /^  [[:alnum:]_-]+:/ && \$0 !~ /^  localstack:/ { exit }
        in_block { print }
      " "$file")"
      grep -F "healthcheck:" <<<"$block"
      grep -F "_localstack/health" <<<"$block"
    done
  '
  assert_success
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
