#!/bin/bash
set -e

echo "============================================"
echo "Preparing test data for load tests"
echo "============================================"

# Run the PrepareCustomers script with k6
docker run --rm \
  --network=host \
  -v "$(pwd)/tests/Load:/app" \
  -w /app \
  k6 run scripts/PrepareCustomers.js

echo ""
echo "============================================"
echo "Test data preparation completed!"
echo "============================================"
