FROM composer:2 AS composer

FROM php:8.3-cli-bookworm

ARG DEBIAN_FRONTEND=noninteractive
ARG KUBECTL_VERSION=v1.35.3
ARG NODE_MAJOR=20
ARG CODEX_NPM_PACKAGE=@openai/codex
ARG TARGETARCH=amd64

ENV SANDBOX_WORKSPACE_ROOT=/workspace/core-service
ENV NPM_CONFIG_UPDATE_NOTIFIER=false

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        bash \
        ca-certificates \
        curl \
        git \
        gnupg \
        jq \
        make \
        openssh-client \
        procps \
        tar \
        unzip \
        xz-utils \
        zip \
    && mkdir -p /etc/apt/keyrings /usr/share/keyrings \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key \
        | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_${NODE_MAJOR}.x nodistro main" \
        > /etc/apt/sources.list.d/nodesource.list \
    && curl -fsSL https://cli.github.com/packages/githubcli-archive-keyring.gpg \
        | gpg --dearmor -o /usr/share/keyrings/githubcli-archive-keyring.gpg \
    && chmod go+r /usr/share/keyrings/githubcli-archive-keyring.gpg \
    && echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/githubcli-archive-keyring.gpg] https://cli.github.com/packages stable main" \
        > /etc/apt/sources.list.d/github-cli.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        gh \
        nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY ops/sandbox-poc/workspace/bootstrap-agent.sh /usr/local/bin/bootstrap-agent

RUN curl -fsSL -o /usr/local/bin/kubectl \
        "https://dl.k8s.io/release/${KUBECTL_VERSION}/bin/linux/${TARGETARCH}/kubectl" \
    && chmod +x /usr/local/bin/kubectl \
    && chmod +x /usr/local/bin/bootstrap-agent \
    && npm install -g "${CODEX_NPM_PACKAGE}" \
    && corepack enable \
    && mkdir -p "${SANDBOX_WORKSPACE_ROOT}" /workspace \
    && git config --global --add safe.directory "${SANDBOX_WORKSPACE_ROOT}"

WORKDIR /workspace/core-service

CMD ["/bin/bash", "-lc", "mkdir -p \"${SANDBOX_WORKSPACE_ROOT}\" && exec sleep infinity"]
