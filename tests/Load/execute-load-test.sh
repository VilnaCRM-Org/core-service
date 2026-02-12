#!/bin/bash
set -euo pipefail

if [ -z "${1:-}" ]; then
    echo "Error: scenario not provided."
    exit 1
fi

if [ -z "${2:-}" ]; then
    echo "Error: runSmoke not provided."
    exit 1
fi

if [ -z "${3:-}" ]; then
    echo "Error: runAverage not provided."
    exit 1
fi

if [ -z "${4:-}" ]; then
    echo "Error: runStress not provided."
    exit 1
fi

if [ -z "${5:-}" ]; then
    echo "Error: runSpike not provided."
    exit 1
fi

scenario_path=$1
scenario_name="$(basename "$scenario_path")"
runSmoke=$2
runAverage=$3
runStress=$4
runSpike=$5
htmlPrefix=${6:-}

# Read results directory from config
CONFIG_FILE="./tests/Load/config.json.dist"
if [ ! -f "$CONFIG_FILE" ]; then
    echo "Error: config.json not found. Please copy config.json.dist to config.json"
    exit 1
fi

RESULTS_DIR=$(grep -o '"resultsDirectory"[[:space:]]*:[[:space:]]*"[^"]*"' "$CONFIG_FILE" | sed 's/.*"resultsDirectory"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/')

if [ -z "$RESULTS_DIR" ]; then
    echo "Error: resultsDirectory not found in config.json"
    exit 1
fi

K6="docker run --user $(id -u):$(id -g) -v ./tests/Load:/loadTests --net=host --rm \
    k6 run --summary-trend-stats='avg,min,med,max,p(95),p(99)' \
    --out 'web-dashboard=period=1s&export=/loadTests/${RESULTS_DIR}/${htmlPrefix}${scenario_name}.html'"

# Prepare customers for all Customer scenarios EXCEPT create scenarios
# Also include cachePerformance which needs pre-existing customers
if [[ $scenario_name != "createCustomer" \
      && $scenario_name != "graphQLCreateCustomer" \
      && ($scenario_name == *Customer* || $scenario_name == "cachePerformance") \
      && $scenario_name != *CustomerStatus* \
      && $scenario_name != *CustomerType* ]]; then
  eval "$K6" /loadTests/utils/prepareCustomers.js -e scenarioName="${scenario_name}" -e run_smoke="${runSmoke}" -e run_average="${runAverage}" -e run_stress="${runStress}" -e run_spike="${runSpike}"
fi

# Prepare customer statuses for all CustomerStatus scenarios EXCEPT create scenarios
if [[ $scenario_name != "createCustomerStatus" && $scenario_name != "graphQLCreateCustomerStatus" && $scenario_name == *CustomerStatus* ]]; then
  eval "$K6" /loadTests/utils/prepareCustomerStatuses.js -e scenarioName="${scenario_name}" -e run_smoke="${runSmoke}" -e run_average="${runAverage}" -e run_stress="${runStress}" -e run_spike="${runSpike}"
fi

# Prepare customer types for all CustomerType scenarios EXCEPT create scenarios
if [[ $scenario_name != "createCustomerType" && $scenario_name != "graphQLCreateCustomerType" && $scenario_name == *CustomerType* ]]; then
  eval "$K6" /loadTests/utils/prepareCustomerTypes.js -e scenarioName="${scenario_name}" -e run_smoke="${runSmoke}" -e run_average="${runAverage}" -e run_stress="${runStress}" -e run_spike="${runSpike}"
fi

eval "$K6" "/loadTests/scripts/${scenario_path}.js" -e run_smoke="${runSmoke}" -e run_average="${runAverage}" -e run_stress="${runStress}" -e run_spike="${runSpike}"
