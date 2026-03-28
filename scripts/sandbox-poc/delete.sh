#!/usr/bin/env bash
set -euo pipefail

. "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

require_sandbox_id
ensure_cluster

namespace="$(sandbox_namespace)"

if kubectl_ctx get namespace "${namespace}" >/dev/null 2>&1; then
    log "Removing Helm release from ${namespace}"
    helm_ctx uninstall "${SANDBOX_POC_RELEASE_NAME}" --namespace "${namespace}" >/dev/null 2>&1 || true

    log "Deleting namespace ${namespace}"
    kubectl_ctx delete namespace "${namespace}" --wait=true >/dev/null
else
    log "Namespace ${namespace} does not exist"
fi
