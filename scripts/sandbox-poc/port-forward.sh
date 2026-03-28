#!/usr/bin/env bash
set -euo pipefail

. "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

require_sandbox_id
ensure_cluster

namespace="$(sandbox_namespace)"
local_port="${LOCAL_PORT:-$(calc_local_port "${namespace}")}"

log "Port-forwarding ${namespace}/service/$(http_service_name) to http://127.0.0.1:${local_port}"
exec kubectl_ctx -n "${namespace}" port-forward "service/$(http_service_name)" "${local_port}:80"
