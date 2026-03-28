#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/../.." && pwd)"

SANDBOX_POC_BIN_DIR="${ROOT_DIR}/.sandbox-poc/bin"
SANDBOX_POC_KIND_VERSION="${SANDBOX_POC_KIND_VERSION:-v0.31.0}"
SANDBOX_POC_KUBECTL_VERSION="${SANDBOX_POC_KUBECTL_VERSION:-v1.35.3}"
SANDBOX_POC_HELM_VERSION="${SANDBOX_POC_HELM_VERSION:-v4.1.3}"
SANDBOX_POC_CLUSTER_NAME="${SANDBOX_POC_CLUSTER_NAME:-core-service-sandbox}"
SANDBOX_POC_KIND_CONTEXT="kind-${SANDBOX_POC_CLUSTER_NAME}"
SANDBOX_POC_KUBECONFIG="${ROOT_DIR}/.sandbox-poc/kubeconfig"
SANDBOX_POC_KIND_NODE_IMAGE="${SANDBOX_POC_KIND_NODE_IMAGE:-}"
SANDBOX_POC_KIND_CONFIG="${ROOT_DIR}/ops/sandbox-poc/kind/cluster.yaml"
SANDBOX_POC_CHART_DIR="${ROOT_DIR}/ops/sandbox-poc/helm/core-service-sandbox"
SANDBOX_POC_WORKSPACE_DOCKERFILE="${ROOT_DIR}/ops/sandbox-poc/workspace/Dockerfile"
SANDBOX_POC_WORKSPACE_ROOT="${SANDBOX_POC_WORKSPACE_ROOT:-/workspace/core-service}"

SANDBOX_POC_KIND="${SANDBOX_POC_BIN_DIR}/kind"
SANDBOX_POC_KUBECTL="${SANDBOX_POC_BIN_DIR}/kubectl"
SANDBOX_POC_HELM="${SANDBOX_POC_BIN_DIR}/helm"

SANDBOX_POC_RELEASE_NAME="${SANDBOX_POC_RELEASE_NAME:-core-service-sandbox}"
SANDBOX_POC_PHP_IMAGE_REPOSITORY="${SANDBOX_POC_PHP_IMAGE_REPOSITORY:-core-service-sandbox-php}"
SANDBOX_POC_PHP_IMAGE_TAG="${SANDBOX_POC_PHP_IMAGE_TAG:-local}"
SANDBOX_POC_CADDY_IMAGE_REPOSITORY="${SANDBOX_POC_CADDY_IMAGE_REPOSITORY:-core-service-sandbox-caddy}"
SANDBOX_POC_CADDY_IMAGE_TAG="${SANDBOX_POC_CADDY_IMAGE_TAG:-local}"
SANDBOX_POC_WORKSPACE_IMAGE_REPOSITORY="${SANDBOX_POC_WORKSPACE_IMAGE_REPOSITORY:-core-service-sandbox-workspace}"
SANDBOX_POC_WORKSPACE_IMAGE_TAG="${SANDBOX_POC_WORKSPACE_IMAGE_TAG:-local}"

log() {
    printf '[sandbox-poc] %s\n' "$*"
}

warn() {
    printf '[sandbox-poc] warning: %s\n' "$*" >&2
}

die() {
    printf '[sandbox-poc] error: %s\n' "$*" >&2
    exit 1
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || die "Required command not found: $1"
}

require_sandbox_id() {
    [ -n "${SANDBOX_ID:-}" ] || die "SANDBOX_ID is required"
}

sanitize_id() {
    printf '%s' "$1" \
        | tr '[:upper:]' '[:lower:]' \
        | sed -E 's/[^a-z0-9]+/-/g; s/^-+//; s/-+$//; s/-+/-/g'
}

sandbox_namespace() {
    require_sandbox_id
    printf 'sandbox-%s' "$(sanitize_id "${SANDBOX_ID}")"
}

php_image() {
    printf '%s:%s' "${SANDBOX_POC_PHP_IMAGE_REPOSITORY}" "${SANDBOX_POC_PHP_IMAGE_TAG}"
}

caddy_image() {
    printf '%s:%s' "${SANDBOX_POC_CADDY_IMAGE_REPOSITORY}" "${SANDBOX_POC_CADDY_IMAGE_TAG}"
}

workspace_image() {
    printf '%s:%s' "${SANDBOX_POC_WORKSPACE_IMAGE_REPOSITORY}" "${SANDBOX_POC_WORKSPACE_IMAGE_TAG}"
}

http_service_name() {
    printf '%s-http' "${SANDBOX_POC_RELEASE_NAME}"
}

app_deployment_name() {
    printf '%s' "${SANDBOX_POC_RELEASE_NAME}"
}

mongodb_deployment_name() {
    printf '%s-mongodb' "${SANDBOX_POC_RELEASE_NAME}"
}

redis_deployment_name() {
    printf '%s-redis' "${SANDBOX_POC_RELEASE_NAME}"
}

localstack_deployment_name() {
    printf '%s-localstack' "${SANDBOX_POC_RELEASE_NAME}"
}

kind_cluster_exists() {
    "${SANDBOX_POC_KIND}" get clusters 2>/dev/null | grep -Fxq "${SANDBOX_POC_CLUSTER_NAME}"
}

ensure_local_tools() {
    [ -x "${SANDBOX_POC_KIND}" ] || die "kind is not installed in ${SANDBOX_POC_BIN_DIR}. Run scripts/sandbox-poc/install-tools.sh"
    [ -x "${SANDBOX_POC_KUBECTL}" ] || die "kubectl is not installed in ${SANDBOX_POC_BIN_DIR}. Run scripts/sandbox-poc/install-tools.sh"
    [ -x "${SANDBOX_POC_HELM}" ] || die "helm is not installed in ${SANDBOX_POC_BIN_DIR}. Run scripts/sandbox-poc/install-tools.sh"
}

ensure_cluster() {
    ensure_local_tools
    kind_cluster_exists || die "kind cluster ${SANDBOX_POC_CLUSTER_NAME} is not running. Run scripts/sandbox-poc/cluster-up.sh"
}

kubectl_ctx() {
    "${SANDBOX_POC_KUBECTL}" --kubeconfig "${SANDBOX_POC_KUBECONFIG}" --context "${SANDBOX_POC_KIND_CONTEXT}" "$@"
}

helm_ctx() {
    "${SANDBOX_POC_HELM}" "$@"
}

app_pod_name() {
    local namespace="$1"

    app_pod_names "${namespace}" | tail -n 1
}

app_pod_names() {
    local namespace="$1"

    kubectl_ctx -n "${namespace}" get pods \
        -l "app.kubernetes.io/component=app,app.kubernetes.io/instance=${SANDBOX_POC_RELEASE_NAME},app.kubernetes.io/name=${SANDBOX_POC_RELEASE_NAME}" \
        --sort-by=.metadata.creationTimestamp \
        -o custom-columns=NAME:.metadata.name \
        --no-headers \
        | awk 'NF'
}

redis_pod_name() {
    local namespace="$1"

    kubectl_ctx -n "${namespace}" get pods \
        -l "app.kubernetes.io/component=redis,app.kubernetes.io/name=${SANDBOX_POC_RELEASE_NAME}" \
        -o jsonpath='{.items[0].metadata.name}'
}

wait_for_pod_exists() {
    local namespace="$1"
    local selector="$2"
    local timeout="${3:-300}"
    local attempt=0

    until kubectl_ctx -n "${namespace}" get pods -l "${selector}" -o name | grep -q .; do
        attempt=$((attempt + 1))
        [ "${attempt}" -lt "${timeout}" ] || die "Timed out waiting for pod with selector ${selector} in ${namespace}"
        sleep 1
    done
}

wait_for_container_running() {
    local namespace="$1"
    local pod="$2"
    local container="$3"
    local timeout="${4:-600}"
    local attempt=0
    local running=""

    until [ "${running}" = "true" ]; do
        running="$(kubectl_ctx -n "${namespace}" get pod "${pod}" \
            -o jsonpath="{.status.containerStatuses[?(@.name==\"${container}\")].ready}" 2>/dev/null || true)"
        if [ "${running}" = "true" ]; then
            return 0
        fi
        attempt=$((attempt + 1))
        [ "${attempt}" -lt "${timeout}" ] || die "Timed out waiting for container ${container} in pod ${pod}"
        sleep 1
    done
}

wait_for_deployment_available() {
    local namespace="$1"
    local deployment="$2"
    local timeout="${3:-900s}"

    kubectl_ctx -n "${namespace}" wait --for=condition=Available "deployment/${deployment}" --timeout="${timeout}" >/dev/null
}

calc_local_port() {
    local value="$1"
    local checksum

    checksum="$(printf '%s' "${value}" | cksum | awk '{print $1}')"
    printf '%s' $((20000 + (checksum % 20000)))
}

require_json_tools() {
    require_command curl
    require_command jq
    require_command tar
    require_command docker
}

optional_helm_set_args() {
    local args=()

    if [ -n "${GIT_AUTHOR_NAME:-}" ]; then
        args+=(--set-string "app.workspace.gitAuthorName=${GIT_AUTHOR_NAME}")
    fi
    if [ -n "${GIT_AUTHOR_EMAIL:-}" ]; then
        args+=(--set-string "app.workspace.gitAuthorEmail=${GIT_AUTHOR_EMAIL}")
    fi

    printf '%s\n' "${args[@]}"
}
