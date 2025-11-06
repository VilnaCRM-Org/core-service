#!/bin/bash

# Fix OpenAPI spec by replacing "type: iri-reference" with "type: string\n  format: iri-reference"
# This is a workaround for API Platform generating incorrect OpenAPI schemas for IRI references

SPEC_FILE=".github/openapi-spec/spec.yaml"

if [ ! -f "$SPEC_FILE" ]; then
    echo "Error: $SPEC_FILE not found"
    exit 1
fi

# Create a backup
cp "$SPEC_FILE" "$SPEC_FILE.backup"

# Fix the iri-reference type issue
# We need to replace:
#   type: iri-reference
# With:
#   type: string
#   format: iri-reference

# Use sed to fix the issue
sed -i 's/type: iri-reference$/type: string\n                  format: iri-reference/g' "$SPEC_FILE"

echo "✅ Fixed iri-reference types in $SPEC_FILE"
