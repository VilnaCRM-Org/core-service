#!/bin/bash
set -euo pipefail

LOAD_TEST_SCENARIOS=$(./tests/Load/get-load-test-scenarios.sh)
SHARD_INDEX=${SHARD_INDEX:-0}
SHARD_TOTAL=${SHARD_TOTAL:-1}

if ! [[ "$SHARD_INDEX" =~ ^[0-9]+$ ]]; then
  echo "Error: SHARD_INDEX must be a non-negative integer. Received: $SHARD_INDEX"
  exit 1
fi

if ! [[ "$SHARD_TOTAL" =~ ^[1-9][0-9]*$ ]]; then
  echo "Error: SHARD_TOTAL must be a positive integer. Received: $SHARD_TOTAL"
  exit 1
fi

if (( SHARD_INDEX >= SHARD_TOTAL )); then
  echo "Error: SHARD_INDEX must be less than SHARD_TOTAL. Received: $SHARD_INDEX/$SHARD_TOTAL"
  exit 1
fi

SCENARIO_INDEX=0
SELECTED_SCENARIOS=0

while IFS= read -r scenario; do
  [[ -z "$scenario" ]] && continue

  if (( SCENARIO_INDEX % SHARD_TOTAL != SHARD_INDEX )); then
    ((SCENARIO_INDEX += 1))
    continue
  fi

  echo "Running smoke load test scenario [shard ${SHARD_INDEX}/${SHARD_TOTAL}]: $scenario"
  ./tests/Load/execute-load-test.sh "$scenario" true false false false smoke-
  ((SCENARIO_INDEX += 1))
  ((SELECTED_SCENARIOS += 1))
done <<< "$LOAD_TEST_SCENARIOS"

if (( SELECTED_SCENARIOS == 0 )); then
  echo "No scenarios matched shard ${SHARD_INDEX}/${SHARD_TOTAL}. Skipping."
fi
