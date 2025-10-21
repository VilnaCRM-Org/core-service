#!/bin/bash
set -e

echo "=== Preparing test data ==="
./tests/Load/prepare-test-data.sh

echo ""
echo "=== Running smoke load tests ==="

LOAD_TEST_SCENARIOS=$(./tests/Load/get-load-test-scenarios.sh)

if [ -z "$LOAD_TEST_SCENARIOS" ]; then
  echo "Error: No load test scenarios found."
  exit 1
fi

for scenario in $LOAD_TEST_SCENARIOS; do
  ./tests/Load/execute-load-test.sh "$scenario" true false false false smoke-
done

echo ""
echo "=== Load tests completed ==="
echo "Note: Test data is still in database. Run 'make cleanup-test-data' to remove it."
