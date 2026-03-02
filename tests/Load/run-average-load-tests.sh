#!/bin/bash
set -euo pipefail

LOAD_TEST_SCENARIOS=$(./tests/Load/get-load-test-scenarios.sh)

while IFS= read -r scenario; do
  [[ -z "$scenario" ]] && continue
  ./tests/Load/execute-load-test.sh "$scenario" false true false false average-
done <<< "$LOAD_TEST_SCENARIOS"
