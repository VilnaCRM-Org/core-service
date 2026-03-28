#!/usr/bin/env bash
set -euo pipefail

. "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

"${SCRIPT_DIR}/install-tools.sh"
mkdir -p "$(dirname "${SANDBOX_POC_KUBECONFIG}")"

if kind_cluster_exists; then
    log "kind cluster ${SANDBOX_POC_CLUSTER_NAME} already exists"
else
    log "Creating kind cluster ${SANDBOX_POC_CLUSTER_NAME}"
    cluster_args=(
        --name "${SANDBOX_POC_CLUSTER_NAME}"
        --config "${SANDBOX_POC_KIND_CONFIG}"
    )

    if [ -n "${SANDBOX_POC_KIND_NODE_IMAGE:-}" ]; then
        cluster_args+=(--image "${SANDBOX_POC_KIND_NODE_IMAGE}")
    fi

    "${SANDBOX_POC_KIND}" create cluster "${cluster_args[@]}"
fi

"${SANDBOX_POC_KIND}" export kubeconfig \
    --name "${SANDBOX_POC_CLUSTER_NAME}" \
    --kubeconfig "${SANDBOX_POC_KUBECONFIG}" >/dev/null

kubectl_ctx cluster-info >/dev/null
log "Cluster ${SANDBOX_POC_CLUSTER_NAME} is ready"
