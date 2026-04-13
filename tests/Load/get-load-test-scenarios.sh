#!/bin/bash
set -euo pipefail

SCRIPT_ROOT="./tests/Load/scripts"
SCENARIO_OVERRIDE=${LOAD_TEST_SCENARIOS:-}
EXCLUDED_FILES=(
  "cleanupCustomers.js"
  "prepareCustomers.js"
  "insertCustomers.js"
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
  mapfile -t override_scenarios < <(
    printf '%s\n' "$SCENARIO_OVERRIDE" |
      sed -E 's/[[:space:],]+/\n/g' |
      sed '/^$/d' |
      sort -u
  )

  missing_scenarios=()
  for scenario in "${override_scenarios[@]}"; do
    if [[ ! -f "$SCRIPT_ROOT/${scenario}.js" ]]; then
      missing_scenarios+=("$SCRIPT_ROOT/${scenario}.js")
    fi
  done

  if (( ${#missing_scenarios[@]} > 0 )); then
    printf 'Error: Unknown load test scenario override(s):\n' >&2
    printf ' - %s\n' "${missing_scenarios[@]}" >&2
    exit 1
  fi

  printf '%s\n' "${override_scenarios[@]}"
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
