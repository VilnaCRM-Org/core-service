#!/bin/bash
set -e

echo "============================================"
echo "Cleaning up test data from load tests"
echo "============================================"

# Run the CleanupCustomers script with k6
docker run --rm \
  --network=host \
  -v "$(pwd)/tests/Load:/app" \
  -w /app \
  k6 run scripts/CleanupCustomers.js

echo ""
echo "============================================"
echo "Test data cleanup completed!"
echo "============================================"
