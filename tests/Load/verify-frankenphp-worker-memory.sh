#!/bin/bash
set -euo pipefail

loops=${SOAK_ITERATIONS:-3}
service=${WORKER_MEMORY_SERVICE:-php}
report_path=${WORKER_MEMORY_REPORT:-tests/Load/results/frankenphp-worker-memory.txt}
allowed_growth_mib=${WORKER_MEMORY_ALLOWED_GROWTH_MIB:-32}
soak_scenarios=${WORKER_MEMORY_SOAK_SCENARIOS:-health,graphql/graphQLCreateCustomer,graphql/graphQLCreateCustomerType,rest-api/cachePerformance,rest-api/createCustomer,rest-api/createCustomerType}

if ! [[ "$loops" =~ ^[1-9][0-9]*$ ]]; then
    echo "SOAK_ITERATIONS must be a positive integer. Received: '$loops'." >&2
    exit 1
fi

mkdir -p "$(dirname "$report_path")"
: > "$report_path"

container_id=$(docker compose ps -q "$service")

if [[ -z "$container_id" ]]; then
    echo "Unable to resolve running container for service '$service'." >&2
    exit 1
fi

parse_mib() {
    local raw_usage=$1
    local current_usage number unit

    current_usage=$(printf '%s' "$raw_usage" | cut -d/ -f1 | tr -d '[:space:]')
    number=$(printf '%s' "$current_usage" | sed -E 's/^([0-9]+(\.[0-9]+)?).*/\1/')
    unit=$(printf '%s' "$current_usage" | sed -E 's/^[0-9]+(\.[0-9]+)?([A-Za-z]+)$/\2/')

    case "$unit" in
        B)
            echo "0"
            ;;
        KiB)
            awk "BEGIN { printf \"%.2f\", ${number} / 1024 }"
            ;;
        MiB)
            awk "BEGIN { printf \"%.2f\", ${number} }"
            ;;
        GiB)
            awk "BEGIN { printf \"%.2f\", ${number} * 1024 }"
            ;;
        *)
            echo "Unsupported memory unit in docker stats output: '$raw_usage'." >&2
            exit 1
            ;;
    esac
}

measure_memory() {
    local raw_usage mib_usage

    raw_usage=$(docker stats --no-stream --format '{{.MemUsage}}' "$container_id" | head -n 1)

    if [[ -z "$raw_usage" ]]; then
        echo "Unable to collect docker stats for container '$container_id'." >&2
        exit 1
    fi

    mib_usage=$(parse_mib "$raw_usage")
    printf '%s|%s\n' "$mib_usage" "$raw_usage"
}

declare -a samples=()
baseline_sample=$(measure_memory)
baseline=${baseline_sample%%|*}
baseline_raw=${baseline_sample#*|}

printf 'baseline_rss=%s\n' "$baseline_raw" | tee -a "$report_path"

for iteration in $(seq 1 "$loops"); do
    echo "Running worker-mode smoke load soak iteration ${iteration}/${loops}..."
    # Endpoint-wide memory coverage is enforced by the dedicated PHPUnit memory suite.
    # The soak run intentionally uses a bounded, representative mix so the worker-mode
    # regression gate stays deterministic and fits the CI time budget.
    LOAD_TEST_SCENARIOS="$soak_scenarios" \
    K6_SKIP_DURATION_THRESHOLDS="${K6_SKIP_DURATION_THRESHOLDS:-1}" \
    K6_SMOKE_RETRIES="${K6_SMOKE_RETRIES:-2}" \
    K6_SMOKE_RETRY_DELAY_SECONDS="${K6_SMOKE_RETRY_DELAY_SECONDS:-2}" \
    make smoke-load-tests-no-build

    sample=$(measure_memory)
    sample_mib=${sample%%|*}
    sample_raw=${sample#*|}
    samples+=("$sample_mib")

    printf 'iteration=%s rss=%s\n' "$iteration" "$sample_raw" | tee -a "$report_path"
done

last_index=$((${#samples[@]} - 1))
final=${samples[$last_index]}
delta=$(awk "BEGIN { printf \"%.2f\", ${final} - ${baseline} }")
monotonic_growth=true
previous=$baseline

for sample in "${samples[@]}"; do
    if ! awk "BEGIN { exit !(${sample} > ${previous}) }"; then
        monotonic_growth=false
        break
    fi
    previous=$sample
done

{
    printf 'baseline_mib=%s\n' "$baseline"
    printf 'final_mib=%s\n' "$final"
    printf 'delta_mib=%s\n' "$delta"
    printf 'allowed_growth_mib=%s\n' "$allowed_growth_mib"
    printf 'monotonic_growth=%s\n' "$monotonic_growth"
} | tee -a "$report_path"

if awk "BEGIN { exit !(${delta} > ${allowed_growth_mib}) }"; then
    echo "Detected sustained FrankenPHP worker memory growth: delta ${delta} MiB exceeds ${allowed_growth_mib} MiB." >&2
    exit 1
fi

echo "FrankenPHP worker memory trend stayed within the configured guardrail."
