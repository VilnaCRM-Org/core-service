#!/usr/bin/env bash
set -euo pipefail

. "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

require_sandbox_id
require_json_tools

"${SCRIPT_DIR}/cluster-up.sh"
"${SCRIPT_DIR}/build-images.sh"

namespace="$(sandbox_namespace)"
release="${SANDBOX_POC_RELEASE_NAME}"

log "Deploying sandbox ${SANDBOX_ID} into namespace ${namespace}"

helm_args=(
    upgrade
    --install
    "${release}"
    "${SANDBOX_POC_CHART_DIR}"
    --namespace "${namespace}"
    --create-namespace
    --set-string "sandboxId=${SANDBOX_ID}"
    --set-string "images.php.repository=${SANDBOX_POC_PHP_IMAGE_REPOSITORY}"
    --set-string "images.php.tag=${SANDBOX_POC_PHP_IMAGE_TAG}"
    --set-string "images.caddy.repository=${SANDBOX_POC_CADDY_IMAGE_REPOSITORY}"
    --set-string "images.caddy.tag=${SANDBOX_POC_CADDY_IMAGE_TAG}"
    --set-string "images.workspace.repository=${SANDBOX_POC_WORKSPACE_IMAGE_REPOSITORY}"
    --set-string "images.workspace.tag=${SANDBOX_POC_WORKSPACE_IMAGE_TAG}"
)

while IFS= read -r arg; do
    [ -n "${arg}" ] && helm_args+=("${arg}")
done < <(optional_helm_set_args)

helm_ctx "${helm_args[@]}"

wait_for_deployment_available "${namespace}" "$(mongodb_deployment_name)"
wait_for_deployment_available "${namespace}" "$(redis_deployment_name)"
wait_for_deployment_available "${namespace}" "$(localstack_deployment_name)"
wait_for_pod_exists "${namespace}" "app.kubernetes.io/component=app,app.kubernetes.io/name=${SANDBOX_POC_RELEASE_NAME}"

"${SCRIPT_DIR}/sync-repo.sh"
"${SCRIPT_DIR}/bootstrap-agent.sh"

wait_for_deployment_available "${namespace}" "$(app_deployment_name)"

log "Sandbox ${SANDBOX_ID} is ready"
log "Use make sandbox-poc-shell SANDBOX_ID=${SANDBOX_ID} to enter the workspace"
