#!/usr/bin/env bash
set -euo pipefail

. "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

require_sandbox_id
ensure_cluster

namespace="$(sandbox_namespace)"
pod="$(app_pod_name "${namespace}")"
wait_for_container_running "${namespace}" "${pod}" "workspace"

kubectl_ctx -n "${namespace}" exec "${pod}" -c workspace -- /bin/bash -lc '
    set -euo pipefail
    bash --version >/dev/null
    git --version
    curl --version >/dev/null
    jq --version
    make --version >/dev/null
    tar --version >/dev/null
    node --version
    npm --version
    gh --version | head -n 1
    php --version | head -n 1
    composer --version
    codex --version
'
