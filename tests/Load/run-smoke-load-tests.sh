#!/bin/bash
set -euo pipefail

LOAD_TEST_SCENARIOS=$(./tests/Load/get-load-test-scenarios.sh)

while IFS= read -r scenario; do
  [[ -z "$scenario" ]] && continue
  ./tests/Load/execute-load-test.sh "$scenario" true false false false smoke-
done <<< "$LOAD_TEST_SCENARIOS"
