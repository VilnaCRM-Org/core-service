#!/bin/bash
set -euo pipefail

jq -r '.[].id' ./tests/Load/benchmark-scenarios.json
