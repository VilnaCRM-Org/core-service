#!/usr/bin/env bash
set -euo pipefail

. "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

require_sandbox_id
require_json_tools
ensure_cluster

namespace="$(sandbox_namespace)"
wait_for_pod_exists "${namespace}" "app.kubernetes.io/component=app,app.kubernetes.io/name=${SANDBOX_POC_RELEASE_NAME}"
mapfile -t pods < <(app_pod_names "${namespace}")

[ "${#pods[@]}" -gt 0 ] || die "No app pods found in ${namespace}"

for pod in "${pods[@]}"; do
    wait_for_container_running "${namespace}" "${pod}" "workspace"

    log "Cleaning workspace in ${namespace}/${pod}"
    kubectl_ctx -n "${namespace}" exec "${pod}" -c workspace -- /bin/bash -lc \
        "mkdir -p '${SANDBOX_POC_WORKSPACE_ROOT}' && find '${SANDBOX_POC_WORKSPACE_ROOT}' -mindepth 1 -maxdepth 1 -exec rm -rf {} +"

    log "Streaming repository into ${namespace}/${pod}:${SANDBOX_POC_WORKSPACE_ROOT}"
    tar \
        --exclude='coverage' \
        --exclude='var/cache' \
        --exclude='var/log' \
        --exclude='vendor' \
        --exclude='node_modules' \
        --exclude='.sandbox-poc' \
        -C "${ROOT_DIR}" \
        -cf - . \
        | kubectl_ctx -n "${namespace}" exec -i "${pod}" -c workspace -- tar -xf - -C "${SANDBOX_POC_WORKSPACE_ROOT}"
done

log "Repository sync complete"
