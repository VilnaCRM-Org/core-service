#!/usr/bin/env bash
set -euo pipefail

. "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

require_command curl
require_command tar

arch="$(uname -m)"
[ "${arch}" = "x86_64" ] || die "This POC currently supports Linux amd64 only"

mkdir -p "${SANDBOX_POC_BIN_DIR}"

install_kind() {
    if [ -x "${SANDBOX_POC_KIND}" ]; then
        log "kind already installed at ${SANDBOX_POC_KIND}"
        return
    fi

    log "Installing kind ${SANDBOX_POC_KIND_VERSION}"
    curl -fsSL -o "${SANDBOX_POC_KIND}" \
        "https://kind.sigs.k8s.io/dl/${SANDBOX_POC_KIND_VERSION}/kind-linux-amd64"
    chmod +x "${SANDBOX_POC_KIND}"
}

install_kubectl() {
    if [ -x "${SANDBOX_POC_KUBECTL}" ]; then
        log "kubectl already installed at ${SANDBOX_POC_KUBECTL}"
        return
    fi

    log "Installing kubectl ${SANDBOX_POC_KUBECTL_VERSION}"
    curl -fsSL -o "${SANDBOX_POC_KUBECTL}" \
        "https://dl.k8s.io/release/${SANDBOX_POC_KUBECTL_VERSION}/bin/linux/amd64/kubectl"
    chmod +x "${SANDBOX_POC_KUBECTL}"
}

install_helm() {
    local tmp_dir archive

    if [ -x "${SANDBOX_POC_HELM}" ]; then
        log "helm already installed at ${SANDBOX_POC_HELM}"
        return
    fi

    log "Installing helm ${SANDBOX_POC_HELM_VERSION}"
    tmp_dir="$(mktemp -d)"
    archive="${tmp_dir}/helm.tar.gz"
    curl -fsSL -o "${archive}" "https://get.helm.sh/helm-${SANDBOX_POC_HELM_VERSION}-linux-amd64.tar.gz"
    tar -xzf "${archive}" -C "${tmp_dir}"
    mv "${tmp_dir}/linux-amd64/helm" "${SANDBOX_POC_HELM}"
    chmod +x "${SANDBOX_POC_HELM}"
    rm -rf "${tmp_dir}"
}

install_kind
install_kubectl
install_helm

log "Installed tools into ${SANDBOX_POC_BIN_DIR}"
