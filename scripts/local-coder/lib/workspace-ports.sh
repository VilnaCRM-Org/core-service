#!/usr/bin/env bash
# shellcheck shell=bash

cs_workspace_port_in_use() {
    local port="${1:?Missing port}"
    local reserved_ports="${2-}"

    printf '%s\n' "${reserved_ports}" | tr ' ' '\n' | sed '/^$/d' | grep -Fxq "${port}"
}

cs_workspace_append_reserved_port() {
    local reserved_name="${1:?Missing reserved ports variable name}"
    local port="${2:?Missing port}"
    local reserved_ports="${!reserved_name-}"

    if cs_workspace_port_in_use "${port}" "${reserved_ports}"; then
        return 0
    fi

    if [ -n "${reserved_ports}" ]; then
        printf -v "${reserved_name}" '%s %s' "${reserved_ports}" "${port}"
    else
        printf -v "${reserved_name}" '%s' "${port}"
    fi
}

cs_find_available_workspace_port() {
    local start_port="${1:?Missing start port}"
    local reserved_ports="${2-}"
    local port="${start_port}"

    while cs_workspace_port_in_use "${port}" "${reserved_ports}"; do
        port=$((port + 1))
    done

    printf '%s\n' "${port}"
}

cs_ensure_workspace_port() {
    local variable_name="${1:?Missing variable name}"
    local default_port="${2:?Missing default port}"
    local fallback_port="${3:?Missing fallback port}"
    local reserved_name="${4:?Missing reserved ports variable name}"
    local current_value="${!variable_name-}"
    local reserved_ports="${!reserved_name-}"
    local chosen_port="${default_port}"

    if [ -n "${current_value}" ]; then
        cs_workspace_append_reserved_port "${reserved_name}" "${current_value}"
        export "${variable_name}"
        return 0
    fi

    if cs_workspace_port_in_use "${chosen_port}" "${reserved_ports}"; then
        chosen_port="$(cs_find_available_workspace_port "${fallback_port}" "${reserved_ports}")"
    fi

    printf -v "${variable_name}" '%s' "${chosen_port}"
    export "${variable_name}"
    cs_workspace_append_reserved_port "${reserved_name}" "${chosen_port}"
}

cs_ensure_workspace_https_ports() {
    local reserved_name="${1:?Missing reserved ports variable name}"
    local reserved_ports="${!reserved_name-}"
    local https_port="${HTTPS_PORT-}"
    local http3_port="${HTTP3_PORT-}"
    local chosen_port="443"

    if [ -n "${https_port}" ] && [ -z "${http3_port}" ]; then
        HTTP3_PORT="${https_port}"
        export HTTP3_PORT
        cs_workspace_append_reserved_port "${reserved_name}" "${HTTPS_PORT}"
        return 0
    fi

    if [ -n "${http3_port}" ] && [ -z "${https_port}" ]; then
        HTTPS_PORT="${http3_port}"
        export HTTPS_PORT
        cs_workspace_append_reserved_port "${reserved_name}" "${HTTP3_PORT}"
        return 0
    fi

    if [ -n "${https_port}" ] && [ -n "${http3_port}" ]; then
        export HTTPS_PORT HTTP3_PORT
        cs_workspace_append_reserved_port "${reserved_name}" "${HTTPS_PORT}"
        cs_workspace_append_reserved_port "${reserved_name}" "${HTTP3_PORT}"
        return 0
    fi

    if cs_workspace_port_in_use "${chosen_port}" "${reserved_ports}"; then
        chosen_port="$(cs_find_available_workspace_port "18443" "${reserved_ports}")"
    fi

    HTTPS_PORT="${chosen_port}"
    HTTP3_PORT="${chosen_port}"
    export HTTPS_PORT HTTP3_PORT
    cs_workspace_append_reserved_port "${reserved_name}" "${chosen_port}"
}

cs_workspace_running_docker_host_ports() {
    local container_id

    if ! command -v docker >/dev/null 2>&1; then
        return 0
    fi

    if ! docker info >/dev/null 2>&1; then
        return 0
    fi

    while IFS= read -r container_id; do
        [ -n "${container_id}" ] || continue
        docker inspect \
            --format '{{range $port, $bindings := .NetworkSettings.Ports}}{{if $bindings}}{{range $bindings}}{{println .HostPort}}{{end}}{{end}}{{end}}' \
            "${container_id}" 2>/dev/null || true
    done < <(docker ps --format '{{.ID}}' 2>/dev/null || true) | awk 'NF {print $1}' | sort -u
}

cs_should_configure_workspace_ports() {
    [ -f "/.dockerenv" ] \
        || [ "${CODER:-false}" = "true" ] \
        || [ -n "${OPENCLAW_WORKSPACE_ROOT:-}" ] \
        || [ -n "${OPENCLAW_CODER_WORKSPACE_ROOT:-}" ]
}

cs_configure_workspace_port_overrides() {
    local reserved_ports="${1-}"

    if [ -z "${reserved_ports}" ]; then
        reserved_ports="$(cs_workspace_running_docker_host_ports | tr '\n' ' ' | sed 's/[[:space:]]\+/ /g; s/^ //; s/ $//')"
    fi

    cs_ensure_workspace_port HTTP_PORT 80 18080 reserved_ports
    cs_ensure_workspace_https_ports reserved_ports
    cs_ensure_workspace_port DB_PORT 27017 37017 reserved_ports
    cs_ensure_workspace_port REDIS_PORT 6379 36379 reserved_ports
    cs_ensure_workspace_port LOCALSTACK_PORT 4566 14566 reserved_ports
    cs_ensure_workspace_port STRUCTURIZR_PORT 8080 18081 reserved_ports
}
