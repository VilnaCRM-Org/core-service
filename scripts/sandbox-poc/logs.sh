#!/usr/bin/env bash
set -euo pipefail

. "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

require_sandbox_id
ensure_cluster

namespace="$(sandbox_namespace)"

if [ "${FOLLOW:-0}" = "1" ]; then
    exec kubectl_ctx -n "${namespace}" logs "deployment/$(app_deployment_name)" --all-containers=true -f
fi

exec kubectl_ctx -n "${namespace}" logs "deployment/$(app_deployment_name)" --all-containers=true --tail="${TAIL_LINES:-200}"
