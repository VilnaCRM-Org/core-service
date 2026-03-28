#!/usr/bin/env bash
set -euo pipefail

. "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

if [ ! -x "${SANDBOX_POC_KIND}" ]; then
    log "kind is not installed; nothing to do"
    exit 0
fi

if kind_cluster_exists; then
    log "Deleting kind cluster ${SANDBOX_POC_CLUSTER_NAME}"
    "${SANDBOX_POC_KIND}" delete cluster --name "${SANDBOX_POC_CLUSTER_NAME}"
else
    log "kind cluster ${SANDBOX_POC_CLUSTER_NAME} does not exist"
fi

rm -f "${SANDBOX_POC_KUBECONFIG}"
