#!/usr/bin/env bash
set -euo pipefail

. "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

require_sandbox_id
ensure_cluster

namespace="$(sandbox_namespace)"
pod="$(app_pod_name "${namespace}")"
wait_for_container_running "${namespace}" "${pod}" "workspace"

exec kubectl_ctx -n "${namespace}" exec -it "${pod}" -c workspace -- /bin/bash -lc \
    "cd '${SANDBOX_POC_WORKSPACE_ROOT}' && exec /bin/bash -l"
