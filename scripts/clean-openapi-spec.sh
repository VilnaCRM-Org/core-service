#!/bin/bash

# Clean OpenAPI spec by removing properties that are not allowed for path parameters
# According to OpenAPI 3.1 spec:
# - allowEmptyValue is only valid for query parameters
# - allowReserved is only valid for query parameters

SPEC_FILE="${1:-.github/openapi-spec/spec.yaml}"

if [ ! -f "$SPEC_FILE" ]; then
    echo "Error: OpenAPI spec file not found: $SPEC_FILE" >&2
    exit 1
fi

# Count fixes
count=0

# Create temp file
TMP_FILE=$(mktemp)

# Process the file line by line
in_path_param=false
while IFS= read -r line; do
    # Check if we're entering a path parameter section
    if [[ "$line" =~ ^[[:space:]]*-[[:space:]]*$ ]] && [ "$in_path_param" = false ]; then
        in_path_param=true
        echo "$line" >> "$TMP_FILE"
        continue
    fi

    # Check if we're in a path parameter (has "in: path")
    if [ "$in_path_param" = true ] && [[ "$line" =~ in:[[:space:]]*path ]]; then
        echo "$line" >> "$TMP_FILE"
        continue
    fi

    # Reset if we hit a new parameter or section
    if [ "$in_path_param" = true ] && [[ "$line" =~ ^[[:space:]]*-[[:space:]]*$ ]]; then
        in_path_param=false
    fi
    if [ "$in_path_param" = true ] && [[ "$line" =~ ^[[:space:]]{0,4}[a-zA-Z] ]]; then
        in_path_param=false
    fi

    # Skip allowEmptyValue and allowReserved in path parameters
    if [ "$in_path_param" = true ] && ([[ "$line" =~ allowEmptyValue: ]] || [[ "$line" =~ allowReserved: ]]); then
        ((count++))
        continue
    fi

    # Fix phone property type
    if [[ "$line" =~ phone:[[:space:]]*[0-9]+ ]]; then
        line=$(echo "$line" | sed 's/phone: \([0-9]*\)/phone: "\1"/')
        ((count++))
    fi

    echo "$line" >> "$TMP_FILE"
done < "$SPEC_FILE"

# Replace original file
mv "$TMP_FILE" "$SPEC_FILE"

echo "Cleaned OpenAPI spec: $count properties fixed"
exit 0
