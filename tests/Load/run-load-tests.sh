#!/bin/bash
set -euo pipefail

LOAD_TEST_SCENARIOS=$(./tests/Load/get-load-test-scenarios.sh)

for scenario in $LOAD_TEST_SCENARIOS; do
  echo "Running load test scenario: $scenario"
  # Parameters: scenario name, setup, teardown, wait, print-summary, result-prefix
  ./tests/Load/execute-load-test.sh "$scenario" true true true true all-
done
