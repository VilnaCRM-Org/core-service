#!/bin/bash

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
METRICS_DIR="/tmp/phpmetrics-complexity"
METRICS_FILE="${METRICS_DIR}/report.json"
LOCAL_METRICS_FILE="/tmp/phpmetrics-report-$$.json"
FORMAT="${1:-text}"
TOP_N="${2:-20}"

# Cleanup temp file on exit
trap "rm -f ${LOCAL_METRICS_FILE}" EXIT

# Generate PHPMetrics report
echo "🔍 Generating PHPMetrics report..." >&2
docker compose exec php bash -c "
    mkdir -p ${METRICS_DIR} && \
    ./vendor/bin/phpmetrics --report-json=${METRICS_FILE} src/ 2>&1 | grep -E '(Done|Error)' || true
" > /dev/null 2>&1

# Check if report exists
if ! docker compose exec php test -f "${METRICS_FILE}"; then
    echo "Error: PHPMetrics report not found: ${METRICS_FILE}" >&2
    echo "Run PHPMetrics first to generate the report." >&2
    exit 1
fi

# Copy JSON report from container to host
docker compose exec php cat "${METRICS_FILE}" > "${LOCAL_METRICS_FILE}"

echo "📊 Analyzing complexity data..." >&2

# Parse metrics and generate output based on format
if [ "$FORMAT" = "json" ]; then
    # JSON output
    jq -r --arg topN "$TOP_N" '
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
    ' "${LOCAL_METRICS_FILE}"

elif [ "$FORMAT" = "csv" ]; then
    # CSV output
    echo "Class,CCN,WMC,Methods,LLOC,Avg Complexity,Max Complexity,Maintainability Index"
    jq -r --arg topN "$TOP_N" '
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
    ' "${LOCAL_METRICS_FILE}"

else
    # Text output (default)
    echo ""
    echo "╔════════════════════════════════════════════════════════════════════════════════════════╗"
    printf "║                    TOP %-2s MOST COMPLEX CLASSES (PHPMetrics)                          ║\n" "$TOP_N"
    echo "╚════════════════════════════════════════════════════════════════════════════════════════╝"
    echo ""

    # Get the data and format it
    jq -r --arg topN "$TOP_N" '
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
    ' "${LOCAL_METRICS_FILE}" | while IFS='|' read -r rank class ccn wmc methods lloc avg max mi; do
        # Extract values
        RANK_NUM="${rank#RANK:}"
        CLASS_NAME="${class#CLASS:}"
        CCN_VAL="${ccn#CCN:}"
        WMC_VAL="${wmc#WMC:}"
        METHODS_VAL="${methods#METHODS:}"
        LLOC_VAL="${lloc#LLOC:}"
        AVG_VAL="${avg#AVG:}"
        MAX_VAL="${max#MAX:}"
        MI_VAL="${mi#MI:}"

        # Print formatted output
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
fi
