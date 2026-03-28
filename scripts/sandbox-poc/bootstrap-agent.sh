#!/usr/bin/env bash
set -euo pipefail

. "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

require_sandbox_id
ensure_cluster

namespace="$(sandbox_namespace)"
mapfile -t pods < <(app_pod_names "${namespace}")

[ "${#pods[@]}" -gt 0 ] || die "No app pods found in ${namespace}"

for pod in "${pods[@]}"; do
    wait_for_container_running "${namespace}" "${pod}" "workspace"

    log "Bootstrapping workspace agent environment in ${namespace}/${pod}"
    kubectl_ctx -n "${namespace}" exec "${pod}" -c workspace -- /bin/bash -lc \
        "/usr/local/bin/bootstrap-agent '${SANDBOX_POC_WORKSPACE_ROOT}'"
done
