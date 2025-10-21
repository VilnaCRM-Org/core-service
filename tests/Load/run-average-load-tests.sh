#!/bin/bash
set -e

echo "=== Preparing test data ==="
./tests/Load/prepare-test-data.sh

echo ""
echo "=== Running average load tests ==="

LOAD_TEST_SCENARIOS=$(./tests/Load/get-load-test-scenarios.sh)

for scenario in $LOAD_TEST_SCENARIOS; do
  ./tests/Load/execute-load-test.sh "$scenario" false true false false average-
done

echo ""
echo "=== Load tests completed ==="
echo "Note: Test data is still in database. Run 'make cleanup-test-data' to remove it."