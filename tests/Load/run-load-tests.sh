#!/bin/bash
set -euo pipefail

LOAD_TEST_SCENARIOS=$(./tests/Load/get-load-test-scenarios.sh)

while IFS= read -r scenario; do
  [[ -z "$scenario" ]] && continue
  echo "Running load test scenario: $scenario"
  # Parameters: scenario name, setup, teardown, wait, print-summary, result-prefix
  ./tests/Load/execute-load-test.sh "$scenario" true true true true all-
done <<< "$LOAD_TEST_SCENARIOS"
