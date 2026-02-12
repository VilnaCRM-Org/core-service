#!/bin/bash
set -euo pipefail

LOAD_TEST_SCENARIOS=$(./tests/Load/get-load-test-scenarios.sh)
SHARD_INDEX=${SHARD_INDEX:-0}
SHARD_TOTAL=${SHARD_TOTAL:-1}
MAX_RETRIES=${K6_SMOKE_RETRIES:-1}
RETRY_DELAY_SECONDS=${K6_SMOKE_RETRY_DELAY_SECONDS:-2}

if ! [[ "$SHARD_INDEX" =~ ^[0-9]+$ ]]; then
  echo "Error: SHARD_INDEX must be a non-negative integer. Received: $SHARD_INDEX"
  exit 1
fi

if ! [[ "$SHARD_TOTAL" =~ ^[1-9][0-9]*$ ]]; then
  echo "Error: SHARD_TOTAL must be a positive integer. Received: $SHARD_TOTAL"
  exit 1
fi

if ! [[ "$MAX_RETRIES" =~ ^[0-9]+$ ]]; then
  echo "Error: K6_SMOKE_RETRIES must be a non-negative integer. Received: $MAX_RETRIES"
  exit 1
fi

if ! [[ "$RETRY_DELAY_SECONDS" =~ ^[0-9]+$ ]]; then
  echo "Error: K6_SMOKE_RETRY_DELAY_SECONDS must be a non-negative integer. Received: $RETRY_DELAY_SECONDS"
  exit 1
fi

if (( SHARD_INDEX >= SHARD_TOTAL )); then
  echo "Error: SHARD_INDEX must be less than SHARD_TOTAL. Received: $SHARD_INDEX/$SHARD_TOTAL"
  exit 1
fi

run_scenario_with_retries() {
  local scenario="$1"
  local max_attempts=$((MAX_RETRIES + 1))
  local attempt=1

  while (( attempt <= max_attempts )); do
    echo "Running smoke load test scenario [shard ${SHARD_INDEX}/${SHARD_TOTAL}] [attempt ${attempt}/${max_attempts}]: $scenario"

    if ./tests/Load/execute-load-test.sh "$scenario" true false false false smoke-; then
      return 0
    fi

    if (( attempt == max_attempts )); then
      echo "Scenario failed after ${max_attempts} attempt(s): $scenario"
      return 1
    fi

    echo "Scenario failed on attempt ${attempt}/${max_attempts}; retrying in ${RETRY_DELAY_SECONDS}s: $scenario"
    sleep "$RETRY_DELAY_SECONDS"
    ((attempt += 1))
  done
}

SCENARIO_INDEX=0
SELECTED_SCENARIOS=0

while IFS= read -r scenario; do
  [[ -z "$scenario" ]] && continue

  if (( SCENARIO_INDEX % SHARD_TOTAL != SHARD_INDEX )); then
    ((SCENARIO_INDEX += 1))
    continue
  fi

  run_scenario_with_retries "$scenario"
  ((SCENARIO_INDEX += 1))
  ((SELECTED_SCENARIOS += 1))
done <<< "$LOAD_TEST_SCENARIOS"

if (( SELECTED_SCENARIOS == 0 )); then
  echo "No scenarios matched shard ${SHARD_INDEX}/${SHARD_TOTAL}. Skipping."
fi
