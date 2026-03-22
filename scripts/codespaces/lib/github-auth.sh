#!/usr/bin/env bash
# shellcheck shell=bash

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../../.." && pwd)"
# shellcheck source=scripts/local-coder/lib/github-auth.sh
. "${ROOT_DIR}/scripts/local-coder/lib/github-auth.sh"
