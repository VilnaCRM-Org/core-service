#!/bin/bash
set -euo pipefail

loops=${SOAK_ITERATIONS:-3}
service=${WORKER_MEMORY_SERVICE:-caddy}
report_path=${WORKER_MEMORY_REPORT:-tests/Load/results/frankenphp-worker-memory.txt}
allowed_growth_mib=${WORKER_MEMORY_ALLOWED_GROWTH_MIB:-32}

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

for iteration in $(seq 1 "$loops"); do
    echo "Running worker-mode smoke load soak iteration ${iteration}/${loops}..."
    make smoke-load-tests

    sample=$(measure_memory)
    sample_mib=${sample%%|*}
    sample_raw=${sample#*|}
    samples+=("$sample_mib")

    printf 'iteration=%s rss=%s\n' "$iteration" "$sample_raw" | tee -a "$report_path"
done

baseline_index=0
last_index=$((${#samples[@]} - 1))
baseline=${samples[$baseline_index]}
final=${samples[$last_index]}
delta=$(awk "BEGIN { printf \"%.2f\", ${final} - ${baseline} }")
monotonic_growth=true
previous=$baseline

for sample in "${samples[@]:1}"; do
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

if [[ "$monotonic_growth" == "true" ]] && awk "BEGIN { exit !(${delta} > ${allowed_growth_mib}) }"; then
    echo "Detected sustained FrankenPHP worker memory growth: delta ${delta} MiB exceeds ${allowed_growth_mib} MiB." >&2
    exit 1
fi

echo "FrankenPHP worker memory trend stayed within the configured guardrail."
