#!/usr/bin/env bash
set -euo pipefail

. "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/lib.sh"

require_json_tools
ensure_cluster

start_port_forward() {
    local namespace="$1"
    local port="$2"
    local log_file="$3"

    kubectl_ctx -n "${namespace}" port-forward "service/$(http_service_name)" "${port}:80" >"${log_file}" 2>&1 &
    echo $!
}

wait_for_http() {
    local base_url="$1"
    local path="$2"
    local timeout="${3:-180}"
    local attempt=0
    local code=0

    until [ "${code}" = "200" ] || [ "${code}" = "204" ]; do
        code="$(curl -s -o /dev/null -w '%{http_code}' "${base_url}${path}" || true)"
        if [ "${code}" = "200" ] || [ "${code}" = "204" ]; then
            return 0
        fi
        attempt=$((attempt + 1))
        [ "${attempt}" -lt "${timeout}" ] || die "Timed out waiting for ${base_url}${path}"
        sleep 1
    done
}

collection_count() {
    local base_url="$1"
    curl -fsSL "${base_url}/api/customers" | jq -r '(.["hydra:member"] // .member // []) | length'
}

create_type() {
    local base_url="$1"
    local value="$2"

    curl -fsSL -X POST \
        -H 'Content-Type: application/ld+json' \
        -d "{\"value\":\"${value}\"}" \
        "${base_url}/api/customer_types" \
        | jq -r '."@id"'
}

create_status() {
    local base_url="$1"
    local value="$2"

    curl -fsSL -X POST \
        -H 'Content-Type: application/ld+json' \
        -d "{\"value\":\"${value}\"}" \
        "${base_url}/api/customer_statuses" \
        | jq -r '."@id"'
}

create_customer() {
    local base_url="$1"
    local initials="$2"
    local email="$3"
    local type_id="$4"
    local status_id="$5"
    local lead_source="$6"

    jq -n \
        --arg initials "${initials}" \
        --arg email "${email}" \
        --arg phone "1234567890" \
        --arg leadSource "${lead_source}" \
        --arg type "${type_id}" \
        --arg status "${status_id}" \
        --argjson confirmed true \
        '{initials: $initials, email: $email, phone: $phone, leadSource: $leadSource, type: $type, status: $status, confirmed: $confirmed}' \
        | curl -fsSL -X POST \
            -H 'Content-Type: application/ld+json' \
            -d @- \
            "${base_url}/api/customers" \
        | jq -r '."@id"'
}

assert_single_sandbox() {
    local sandbox_id="$1"
    local namespace="sandbox-$(sanitize_id "${sandbox_id}")"
    local port log_file pf_pid redis_pod value

    port="$(calc_local_port "${namespace}")"
    log_file="$(mktemp)"
    pf_pid="$(start_port_forward "${namespace}" "${port}" "${log_file}")"

    cleanup_single() {
        kill "${pf_pid}" >/dev/null 2>&1 || true
        rm -f "${log_file}"
    }
    trap cleanup_single RETURN

    wait_for_http "http://127.0.0.1:${port}" "/api/docs"
    wait_for_http "http://127.0.0.1:${port}" "/api/health"

    kubectl_ctx -n "${namespace}" exec "$(app_pod_name "${namespace}")" -c workspace -- codex --version >/dev/null

    redis_pod="$(redis_pod_name "${namespace}")"
    kubectl_ctx -n "${namespace}" exec "${redis_pod}" -c redis -- redis-cli SET sandbox:smoke "${sandbox_id}" >/dev/null
    value="$(kubectl_ctx -n "${namespace}" exec "${redis_pod}" -c redis -- redis-cli GET sandbox:smoke | tr -d '\r')"
    [ "${value}" = "${sandbox_id}" ] || die "Redis smoke value mismatch in ${namespace}"

    log "Single-sandbox smoke passed for ${sandbox_id}"
}

assert_parallel_sandboxes() {
    local sandbox_a="$1"
    local sandbox_b="$2"
    local namespace_a="sandbox-$(sanitize_id "${sandbox_a}")"
    local namespace_b="sandbox-$(sanitize_id "${sandbox_b}")"
    local port_a port_b log_a log_b pf_a pf_b
    local type_a status_a type_b status_b customer_a customer_b
    local count_a count_b
    local redis_a redis_b value_a value_b

    port_a="$(calc_local_port "${namespace_a}")"
    port_b="$(calc_local_port "${namespace_b}")"
    log_a="$(mktemp)"
    log_b="$(mktemp)"
    pf_a="$(start_port_forward "${namespace_a}" "${port_a}" "${log_a}")"
    pf_b="$(start_port_forward "${namespace_b}" "${port_b}" "${log_b}")"

    cleanup_parallel() {
        kill "${pf_a}" >/dev/null 2>&1 || true
        kill "${pf_b}" >/dev/null 2>&1 || true
        rm -f "${log_a}" "${log_b}"
    }
    trap cleanup_parallel RETURN

    wait_for_http "http://127.0.0.1:${port_a}" "/api/docs"
    wait_for_http "http://127.0.0.1:${port_b}" "/api/docs"
    wait_for_http "http://127.0.0.1:${port_a}" "/api/health"
    wait_for_http "http://127.0.0.1:${port_b}" "/api/health"

    kubectl_ctx -n "${namespace_a}" exec "$(app_pod_name "${namespace_a}")" -c workspace -- codex --version >/dev/null
    kubectl_ctx -n "${namespace_b}" exec "$(app_pod_name "${namespace_b}")" -c workspace -- codex --version >/dev/null

    count_a="$(collection_count "http://127.0.0.1:${port_a}")"
    count_b="$(collection_count "http://127.0.0.1:${port_b}")"
    [ "${count_a}" = "0" ] || die "Expected zero customers in ${namespace_a}, found ${count_a}"
    [ "${count_b}" = "0" ] || die "Expected zero customers in ${namespace_b}, found ${count_b}"

    type_a="$(create_type "http://127.0.0.1:${port_a}" "type-${sandbox_a}")"
    status_a="$(create_status "http://127.0.0.1:${port_a}" "status-${sandbox_a}")"
    customer_a="$(create_customer "http://127.0.0.1:${port_a}" "${sandbox_a}" "${sandbox_a}@example.com" "${type_a}" "${status_a}" "sandbox-a")"
    [ -n "${customer_a}" ] || die "Failed to create customer in ${namespace_a}"

    count_a="$(collection_count "http://127.0.0.1:${port_a}")"
    count_b="$(collection_count "http://127.0.0.1:${port_b}")"
    [ "${count_a}" = "1" ] || die "Expected one customer in ${namespace_a}, found ${count_a}"
    [ "${count_b}" = "0" ] || die "Expected zero customers in ${namespace_b} after A write, found ${count_b}"

    type_b="$(create_type "http://127.0.0.1:${port_b}" "type-${sandbox_b}")"
    status_b="$(create_status "http://127.0.0.1:${port_b}" "status-${sandbox_b}")"
    customer_b="$(create_customer "http://127.0.0.1:${port_b}" "${sandbox_b}" "${sandbox_b}@example.com" "${type_b}" "${status_b}" "sandbox-b")"
    [ -n "${customer_b}" ] || die "Failed to create customer in ${namespace_b}"

    count_a="$(collection_count "http://127.0.0.1:${port_a}")"
    count_b="$(collection_count "http://127.0.0.1:${port_b}")"
    [ "${count_a}" = "1" ] || die "Expected one customer in ${namespace_a} after B write, found ${count_a}"
    [ "${count_b}" = "1" ] || die "Expected one customer in ${namespace_b} after B write, found ${count_b}"

    redis_a="$(redis_pod_name "${namespace_a}")"
    redis_b="$(redis_pod_name "${namespace_b}")"
    kubectl_ctx -n "${namespace_a}" exec "${redis_a}" -c redis -- redis-cli SET sandbox:isolation "${sandbox_a}" >/dev/null

    value_a="$(kubectl_ctx -n "${namespace_a}" exec "${redis_a}" -c redis -- redis-cli GET sandbox:isolation | tr -d '\r')"
    value_b="$(kubectl_ctx -n "${namespace_b}" exec "${redis_b}" -c redis -- redis-cli GET sandbox:isolation | tr -d '\r')"
    [ "${value_a}" = "${sandbox_a}" ] || die "Redis key missing in ${namespace_a}"
    [ -z "${value_b}" ] || die "Redis key leaked into ${namespace_b}"

    log "Parallel sandbox isolation smoke passed for ${sandbox_a} and ${sandbox_b}"
}

if [ -n "${SANDBOX_A:-}" ] && [ -n "${SANDBOX_B:-}" ]; then
    assert_parallel_sandboxes "${SANDBOX_A}" "${SANDBOX_B}"
else
    require_sandbox_id
    assert_single_sandbox "${SANDBOX_ID}"
fi
