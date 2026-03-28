#!/usr/bin/env bash
set -euo pipefail

. "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

require_command docker
ensure_local_tools

ensure_image() {
    local image="$1"
    shift

    if docker image inspect "${image}" >/dev/null 2>&1 && [ "${FORCE_BUILD:-0}" != "1" ]; then
        log "reusing image ${image}"
    else
        log "building image ${image}"
        docker build \
            --platform linux/amd64 \
            -t "${image}" \
            "$@"
    fi
}

ensure_image "$(php_image)" \
    --target app_php_dev \
    -f "${ROOT_DIR}/Dockerfile" \
    "${ROOT_DIR}"

ensure_image "$(caddy_image)" \
    --target app_caddy \
    -f "${ROOT_DIR}/Dockerfile" \
    "${ROOT_DIR}"

ensure_image "$(workspace_image)" \
    -f "${SANDBOX_POC_WORKSPACE_DOCKERFILE}" \
    "${ROOT_DIR}"

if kind_cluster_exists; then
    log "Loading images into kind cluster ${SANDBOX_POC_CLUSTER_NAME}"
    "${SANDBOX_POC_KIND}" load docker-image \
        --name "${SANDBOX_POC_CLUSTER_NAME}" \
        "$(php_image)" \
        "$(caddy_image)" \
        "$(workspace_image)"
fi

log "Sandbox images are ready"
