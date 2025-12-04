#!/bin/bash

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
METRICS_DIR="/tmp/phpmetrics-complexity"
METRICS_FILE="${METRICS_DIR}/report.json"
LOCAL_METRICS_FILE="/tmp/phpmetrics-report-$$.json"
FORMAT="${1:-text}"
TOP_N="${2:-20}"

trap 'rm -f "${LOCAL_METRICS_FILE}"' EXIT

generate_phpmetrics_report() {
    echo "🔍 Generating PHPMetrics report..." >&2
    docker compose exec php bash -c "
        mkdir -p ${METRICS_DIR} && \
        ./vendor/bin/phpmetrics --report-json=${METRICS_FILE} src/ 2>&1 | grep -E '(Done|Error)' || true
    " > /dev/null 2>&1
}

check_report_exists() {
    if ! docker compose exec php sh -c "[ -f '${METRICS_FILE}' ]"; then
        echo "Error: PHPMetrics report not found: ${METRICS_FILE}" >&2
        echo "Run PHPMetrics first to generate the report." >&2
        exit 1
    fi
}

copy_report_from_container() {
    docker compose exec php cat "${METRICS_FILE}" > "${LOCAL_METRICS_FILE}"
}

format_json() {
    local top_n="$1"
    local file="$2"

    jq -r --arg topN "$top_n" '
      to_entries
      | map(select(.value.ccn != null and .value.nbMethods != null and (.key | endswith("\\") | not)))
      | map({
          class: .key,
          ccn: (.value.ccn | tonumber),
          wmc: ((.value.wmc // 0) | tonumber),
          methods: (.value.nbMethods | tonumber),
          lloc: ((.value.lloc // 0) | tonumber),
          avgComplexity: (if (.value.nbMethods | tonumber) > 0 then ((.value.ccn | tonumber) / (.value.nbMethods | tonumber) | . * 100 | round / 100) else 0 end),
          maxComplexity: ((.value.ccnMethodMax // 0) | tonumber),
          maintainabilityIndex: ((.value.mi // 0) | tonumber | . * 100 | round / 100)
        })
      | sort_by(.ccn) | reverse
      | .[:($topN | tonumber)]
    ' "$file"
}

format_csv() {
    local top_n="$1"
    local file="$2"

    echo "Class,CCN,WMC,Methods,LLOC,Avg Complexity,Max Complexity,Maintainability Index"
    jq -r --arg topN "$top_n" '
      to_entries
      | map(select(.value.ccn != null and .value.nbMethods != null and (.key | endswith("\\") | not)))
      | map({
          class: .key,
          ccn: (.value.ccn | tonumber),
          wmc: ((.value.wmc // 0) | tonumber),
          methods: (.value.nbMethods | tonumber),
          lloc: ((.value.lloc // 0) | tonumber),
          avgComplexity: (if (.value.nbMethods | tonumber) > 0 then ((.value.ccn | tonumber) / (.value.nbMethods | tonumber) | . * 100 | round / 100) else 0 end),
          maxComplexity: ((.value.ccnMethodMax // 0) | tonumber),
          maintainabilityIndex: ((.value.mi // 0) | tonumber | . * 100 | round / 100)
        })
      | sort_by(.ccn) | reverse
      | .[:($topN | tonumber)]
      | .[]
      | [.class, .ccn, .wmc, .methods, .lloc, .avgComplexity, .maxComplexity, .maintainabilityIndex]
      | @csv
    ' "$file"
}

format_text() {
    local top_n="$1"
    local file="$2"

    echo ""
    echo "╔════════════════════════════════════════════════════════════════════════════════════════╗"
    printf "║                    TOP %-2s MOST COMPLEX CLASSES (PHPMetrics)                          ║\n" "$top_n"
    echo "╚════════════════════════════════════════════════════════════════════════════════════════╝"
    echo ""

    jq -r --arg topN "$top_n" '
      to_entries
      | map(select(.value.ccn != null and .value.nbMethods != null and (.key | endswith("\\") | not)))
      | map({
          class: .key,
          ccn: (.value.ccn | tonumber),
          wmc: ((.value.wmc // 0) | tonumber),
          methods: (.value.nbMethods | tonumber),
          lloc: ((.value.lloc // 0) | tonumber),
          avgComplexity: (if (.value.nbMethods | tonumber) > 0 then ((.value.ccn | tonumber) / (.value.nbMethods | tonumber) | . * 100 | round / 100) else 0 end),
          maxComplexity: ((.value.ccnMethodMax // 0) | tonumber),
          maintainabilityIndex: ((.value.mi // 0) | tonumber | . * 100 | round / 100)
        })
      | sort_by(.ccn) | reverse
      | .[:($topN | tonumber)]
      | to_entries
      | .[]
      | "RANK:\(.key + 1)|CLASS:\(.value.class)|CCN:\(.value.ccn)|WMC:\(.value.wmc)|METHODS:\(.value.methods)|LLOC:\(.value.lloc)|AVG:\(.value.avgComplexity)|MAX:\(.value.maxComplexity)|MI:\(.value.maintainabilityIndex)"
    ' "$file" | while IFS='|' read -r rank class ccn wmc methods lloc avg max mi; do
        RANK_NUM="${rank#RANK:}"
        CLASS_NAME="${class#CLASS:}"
        CCN_VAL="${ccn#CCN:}"
        WMC_VAL="${wmc#WMC:}"
        METHODS_VAL="${methods#METHODS:}"
        LLOC_VAL="${lloc#LLOC:}"
        AVG_VAL="${avg#AVG:}"
        MAX_VAL="${max#MAX:}"
        MI_VAL="${mi#MI:}"

        printf "#%s - %s\n" "$RANK_NUM" "$CLASS_NAME"
        printf "%s\n" "──────────────────────────────────────────────────────────────────────────────────────────"
        printf "  🔢 Cyclomatic Complexity (CCN):    %s\n" "$CCN_VAL"
        printf "  🎯 Weighted Method Count (WMC):    %s\n" "$WMC_VAL"
        printf "  📊 Methods:                        %s\n" "$METHODS_VAL"
        printf "  📏 Logical Lines of Code (LLOC):   %s\n" "$LLOC_VAL"
        printf "  ⚡ Avg Complexity per Method:       %s\n" "$AVG_VAL"
        printf "  🔴 Max Method Complexity:          %s\n" "$MAX_VAL"
        printf "  💚 Maintainability Index:          %s\n" "$MI_VAL"
        echo ""
    done

    echo ""
    echo "Legend:"
    echo "  - CCN: Cyclomatic Complexity Number (total decision points)"
    echo "  - WMC: Weighted Method Count (sum of all method complexities)"
    echo "  - Avg Complexity: CCN ÷ Methods (target: < 5 per PHPInsights)"
    echo "  - Max Complexity: Highest complexity of any single method"
    echo "  - Maintainability Index: 0-100, higher is better (> 65 is good)"
    echo ""
}

generate_phpmetrics_report
check_report_exists
copy_report_from_container

echo "📊 Analyzing complexity data..." >&2

if [ "$FORMAT" = "json" ]; then
    format_json "$TOP_N" "${LOCAL_METRICS_FILE}"
elif [ "$FORMAT" = "csv" ]; then
    format_csv "$TOP_N" "${LOCAL_METRICS_FILE}"
else
    format_text "$TOP_N" "${LOCAL_METRICS_FILE}"
fi
