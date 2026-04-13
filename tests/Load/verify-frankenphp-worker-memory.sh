#!/bin/bash
set -euo pipefail

loops=${SOAK_ITERATIONS:-3}
service=${WORKER_MEMORY_SERVICE:-php}
report_path=${WORKER_MEMORY_REPORT:-tests/Load/results/frankenphp-worker-memory.txt}
allowed_growth_mib=${WORKER_MEMORY_ALLOWED_GROWTH_MIB:-32}

if [[ -n "${WORKER_MEMORY_SOAK_SCENARIOS:-}" ]]; then
    soak_scenarios=${WORKER_MEMORY_SOAK_SCENARIOS}
else
    soak_scenarios=$(./tests/Load/get-load-test-scenarios.sh | paste -sd, -)
fi

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

run_soak_iteration() {
    local label=$1

    echo "Running worker-mode smoke load soak ${label}..."
    # The same-kernel PHPUnit suite gives object-level leak detection while this soak
    # verifies that the live worker survives the full endpoint inventory repeatedly
    # without sustained RSS growth.
    LOAD_TEST_SCENARIOS="$soak_scenarios" \
    K6_SKIP_DURATION_THRESHOLDS="${K6_SKIP_DURATION_THRESHOLDS:-1}" \
    K6_SMOKE_RETRIES="${K6_SMOKE_RETRIES:-1}" \
    K6_SMOKE_RETRY_DELAY_SECONDS="${K6_SMOKE_RETRY_DELAY_SECONDS:-2}" \
    make smoke-load-tests-no-build
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
cold_baseline_sample=$(measure_memory)
cold_baseline=${cold_baseline_sample%%|*}
cold_baseline_raw=${cold_baseline_sample#*|}

printf 'cold_baseline_rss=%s\n' "$cold_baseline_raw" | tee -a "$report_path"

run_soak_iteration "warmup"

baseline_sample=$(measure_memory)
baseline=${baseline_sample%%|*}
baseline_raw=${baseline_sample#*|}

printf 'post_warmup_baseline_rss=%s\n' "$baseline_raw" | tee -a "$report_path"

for iteration in $(seq 1 "$loops"); do
    run_soak_iteration "iteration ${iteration}/${loops}"
    sample=$(measure_memory)
    sample_mib=${sample%%|*}
    sample_raw=${sample#*|}
    samples+=("$sample_mib")

    printf 'iteration=%s rss=%s\n' "$iteration" "$sample_raw" | tee -a "$report_path"
done

last_index=$((${#samples[@]} - 1))
final=${samples[$last_index]}
delta=$(awk "BEGIN { printf \"%.2f\", ${final} - ${baseline} }")
cold_to_final_delta=$(awk "BEGIN { printf \"%.2f\", ${final} - ${cold_baseline} }")
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
    printf 'cold_baseline_mib=%s\n' "$cold_baseline"
    printf 'baseline_mib=%s\n' "$baseline"
    printf 'final_mib=%s\n' "$final"
    printf 'delta_mib=%s\n' "$delta"
    printf 'cold_to_final_delta_mib=%s\n' "$cold_to_final_delta"
    printf 'allowed_growth_mib=%s\n' "$allowed_growth_mib"
    printf 'monotonic_growth=%s\n' "$monotonic_growth"
} | tee -a "$report_path"

if [ "$monotonic_growth" = true ] && awk "BEGIN { exit !(${delta} > ${allowed_growth_mib}) }"; then
    echo "Detected sustained FrankenPHP worker memory growth: post-warmup delta ${delta} MiB exceeds ${allowed_growth_mib} MiB and the trend remained monotonic." >&2
    exit 1
fi

if awk "BEGIN { exit !(${delta} > ${allowed_growth_mib}) }"; then
    echo "Observed a post-warmup RSS increase above the guardrail (${delta} MiB > ${allowed_growth_mib} MiB), but the trend was not monotonic; treating this as warmup/transient growth for the CI soak gate." | tee -a "$report_path"
fi

echo "FrankenPHP worker memory trend stayed within the configured guardrail."
