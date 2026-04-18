#!/bin/bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

BENCHMARK_VUS=${BENCHMARK_VUS:-10}
BENCHMARK_DURATION_SECONDS=${BENCHMARK_DURATION_SECONDS:-30}
BENCHMARK_WARMUP_DURATION_SECONDS=${BENCHMARK_WARMUP_DURATION_SECONDS:-5}
BENCHMARK_FIXTURE_POOL_SIZE=${BENCHMARK_FIXTURE_POOL_SIZE:-100}
BENCHMARK_DELETE_WARMUP_REQUESTS=${BENCHMARK_DELETE_WARMUP_REQUESTS:-10000}
BENCHMARK_DELETE_REQUEST_BUFFER_PERCENT=${BENCHMARK_DELETE_REQUEST_BUFFER_PERCENT:-15}
BENCHMARK_DELETE_MIN_FIXTURE_POOL=${BENCHMARK_DELETE_MIN_FIXTURE_POOL:-10000}
BENCHMARK_RESULTS_SUBDIR=${BENCHMARK_RESULTS_SUBDIR:-runtime-comparison/manual}
BENCHMARK_MODE_LABEL=${BENCHMARK_MODE_LABEL:-manual}
BENCHMARK_FIXTURE_SUFFIX=${LOAD_TEST_FIXTURE_SUFFIX:-$(printf '%s' "$BENCHMARK_MODE_LABEL" | tr -cs '[:alnum:]' '-' | sed 's/^-//; s/-$//' | tr '[:upper:]' '[:lower:]')}

RESULTS_DIR="tests/Load/results/${BENCHMARK_RESULTS_SUBDIR}"
RESULTS_MOUNT_DIR="/loadTests/results/${BENCHMARK_RESULTS_SUBDIR}"
BENCHMARK_FAILURES_LOG="${RESULTS_DIR}/threshold-failures.log"
mkdir -p "$RESULTS_DIR"
: > "$BENCHMARK_FAILURES_LOG"

if [[ -n "${BENCHMARK_SCENARIOS:-}" ]]; then
  readarray -t SCENARIOS < <(printf '%s\n' "${BENCHMARK_SCENARIOS}" | tr ',' '\n' | sed '/^$/d')
else
  readarray -t SCENARIOS < <(./tests/Load/get-benchmark-scenarios.sh)
fi

if [[ ${#SCENARIOS[@]} -eq 0 ]]; then
  echo "No benchmark scenarios were selected."
  exit 1
fi

SELECTED_SCENARIOS_JSON="$(printf '%s\n' "${SCENARIOS[@]}" | jq -R . | jq -s '.')"
BENCHMARK_SCENARIOS_JSON="$(jq -c --argjson selected "$SELECTED_SCENARIOS_JSON" '
  map(select(.id as $id | $selected | index($id)))
' ./tests/Load/benchmark-scenarios.json)"

is_delete_scenario() {
  local scenario="$1"
  local scenario_name
  scenario_name="$(basename "$scenario")"
  [[ "${scenario_name,,}" == *delete* ]]
}

run_benchmark() {
  local scenario="$1"
  local duration_seconds="$2"
  local summary_path="$3"
  local expected_requests="$4"
  local log_path="$5"
  local host_summary_path="$6"

  local extra_args=(
    "-e" "run_benchmark=true"
    "-e" "benchmark_vus=${BENCHMARK_VUS}"
    "-e" "benchmark_duration_seconds=${duration_seconds}"
    "-e" "benchmark_fixture_pool_size=${BENCHMARK_FIXTURE_POOL_SIZE}"
    "-e" "benchmark_delete_min_fixture_pool=${BENCHMARK_DELETE_MIN_FIXTURE_POOL}"
  )

  if [[ -n "$expected_requests" ]]; then
    extra_args+=("-e" "benchmark_expected_requests=${expected_requests}")
  fi

  set +e
  K6_DISABLE_WEB_DASHBOARD=true \
  K6_SKIP_DURATION_THRESHOLDS=1 \
  LOAD_TEST_FIXTURE_SUFFIX="$BENCHMARK_FIXTURE_SUFFIX" \
  K6_SUMMARY_EXPORT_PATH="$summary_path" \
  K6_EXTRA_ARGS="${extra_args[*]}" \
    ./tests/Load/execute-load-test.sh "$scenario" false false false false >"$log_path" 2>&1
  local exit_code=$?
  set -e

  if [[ $exit_code -ne 0 ]]; then
    if [[ -f "$host_summary_path" ]]; then
      printf '%s\t%s\t%s\n' "$scenario" "$duration_seconds" "$exit_code" >>"$BENCHMARK_FAILURES_LOG"
      echo "Scenario ${scenario} exited with ${exit_code}; summary exported and recorded in ${BENCHMARK_FAILURES_LOG}."
      return 0
    fi

    return "$exit_code"
  fi
}

for scenario in "${SCENARIOS[@]}"; do
  scenario_slug="${scenario//\//__}"
  warmup_summary="${RESULTS_MOUNT_DIR}/${scenario_slug}.warmup.summary.json"
  benchmark_summary="${RESULTS_MOUNT_DIR}/${scenario_slug}.summary.json"
  warmup_log="${RESULTS_DIR}/${scenario_slug}.warmup.log"
  benchmark_log="${RESULTS_DIR}/${scenario_slug}.log"

  delete_expected_requests=""
  if is_delete_scenario "$scenario"; then
    delete_expected_requests="$BENCHMARK_DELETE_WARMUP_REQUESTS"
  fi

  echo "Warmup: ${scenario}"
  run_benchmark \
    "$scenario" \
    "$BENCHMARK_WARMUP_DURATION_SECONDS" \
    "$warmup_summary" \
    "$delete_expected_requests" \
    "$warmup_log" \
    "${RESULTS_DIR}/${scenario_slug}.warmup.summary.json"

  if is_delete_scenario "$scenario"; then
    warmup_summary_json="${RESULTS_DIR}/${scenario_slug}.warmup.summary.json"
    warmup_count="$(jq -r '
      [
        .metrics["http_reqs{test_type:benchmark}"].count?,
        (.metrics
          | to_entries[]?
          | select(.key | startswith("http_reqs") and test("test_type[:=]benchmark"))
          | .value.count?),
        (.metrics
          | to_entries[]?
          | select(.key | startswith("http_reqs"))
          | .value.count?)
      ]
      | map(select(. != null))
      | .[0] // 0
    ' "$warmup_summary_json")"
    if [[ "$warmup_count" == "0" ]]; then
      echo "WARN: warmup benchmark request count resolved to 0 for ${scenario} from ${warmup_summary_json}; delete calibration will fall back to BENCHMARK_DELETE_MIN_FIXTURE_POOL." >&2
    fi
    delete_expected_requests="$(awk -v count="$warmup_count" -v warmup="$BENCHMARK_WARMUP_DURATION_SECONDS" -v duration="$BENCHMARK_DURATION_SECONDS" -v buffer="$BENCHMARK_DELETE_REQUEST_BUFFER_PERCENT" 'BEGIN {
      if (warmup <= 0) {
        print 0;
        exit;
      }
      projected = (count / warmup) * duration;
      buffered = projected * (1 + (buffer / 100));
      print int(buffered + 0.999999);
    }')"
  fi

  echo "Benchmark: ${scenario}"
  run_benchmark \
    "$scenario" \
    "$BENCHMARK_DURATION_SECONDS" \
    "$benchmark_summary" \
    "$delete_expected_requests" \
    "$benchmark_log" \
    "${RESULTS_DIR}/${scenario_slug}.summary.json"
done

jq -n \
  --arg generatedAt "$(date -Iseconds)" \
  --arg modeLabel "$BENCHMARK_MODE_LABEL" \
  --argjson vus "$BENCHMARK_VUS" \
  --argjson durationSeconds "$BENCHMARK_DURATION_SECONDS" \
  --argjson warmupDurationSeconds "$BENCHMARK_WARMUP_DURATION_SECONDS" \
  --argjson fixturePoolSize "$BENCHMARK_FIXTURE_POOL_SIZE" \
  --argjson deleteWarmupRequests "$BENCHMARK_DELETE_WARMUP_REQUESTS" \
  --argjson deleteBufferPercent "$BENCHMARK_DELETE_REQUEST_BUFFER_PERCENT" \
  --argjson deleteMinFixturePool "$BENCHMARK_DELETE_MIN_FIXTURE_POOL" \
  --arg failureLog "$BENCHMARK_FAILURES_LOG" \
  --argjson scenarios "$BENCHMARK_SCENARIOS_JSON" \
  '{
    generatedAt: $generatedAt,
    modeLabel: $modeLabel,
    vus: $vus,
    durationSeconds: $durationSeconds,
    warmupDurationSeconds: $warmupDurationSeconds,
    fixturePoolSize: $fixturePoolSize,
    deleteWarmupRequests: $deleteWarmupRequests,
    deleteBufferPercent: $deleteBufferPercent,
    deleteMinFixturePool: $deleteMinFixturePool,
    failureLog: $failureLog,
    scenarios: $scenarios
  }' >"${RESULTS_DIR}/metadata.json"

echo "Fixed-VU benchmark artifacts saved under ${RESULTS_DIR}"
