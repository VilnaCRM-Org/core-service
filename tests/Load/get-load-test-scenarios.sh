#!/bin/bash
set -euo pipefail

SCRIPT_ROOT="./tests/Load/scripts"
SCENARIO_OVERRIDE=${LOAD_TEST_SCENARIOS:-}
EXCLUDED_FILES=(
  "cleanupCustomers.js"
  "prepareCustomers.js"
  "insertCustomers.js"
  "getCustomers.js" # temporarily excluded – see README for context
)

contains_excluded_file() {
  local filename="$1"
  for excluded in "${EXCLUDED_FILES[@]}"; do
    if [[ "$filename" == "$excluded" ]]; then
      return 0
    fi
  done

  return 1
}

if [[ ! -d "$SCRIPT_ROOT" ]]; then
  echo "Error: $SCRIPT_ROOT not found" >&2
  exit 1
fi

if [[ -n "$SCENARIO_OVERRIDE" ]]; then
  printf '%s\n' "$SCENARIO_OVERRIDE" |
    tr ', ' '\n' |
    sed '/^$/d' |
    sort -u
  exit 0
fi

find "$SCRIPT_ROOT" -type f -name "*.js" -print0 |
  while IFS= read -r -d '' file; do
    filename="$(basename "$file")"
    if contains_excluded_file "$filename"; then
      continue
    fi

    relative_path="${file#${SCRIPT_ROOT}/}"
    scenario="${relative_path%.js}"
    echo "$scenario"
  done | sort
