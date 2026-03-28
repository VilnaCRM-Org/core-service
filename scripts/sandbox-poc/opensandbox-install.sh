#!/usr/bin/env bash
set -euo pipefail

. "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

ensure_cluster

version="${OPENSANDBOX_CONTROLLER_VERSION:-0.1.0}"
namespace="${OPENSANDBOX_NAMESPACE:-opensandbox-system}"
chart_url="https://github.com/alibaba/OpenSandbox/releases/download/helm/opensandbox-controller/${version}/opensandbox-controller-${version}.tgz"

log "Installing experimental OpenSandbox controller ${version} into ${namespace}"
helm_ctx upgrade --install \
    opensandbox-controller \
    "${chart_url}" \
    --namespace "${namespace}" \
    --create-namespace

kubectl_ctx -n "${namespace}" wait --for=condition=Available deployment --all --timeout=300s >/dev/null
log "OpenSandbox controller is installed"
