#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

readonly SHIM_SCRIPT="${SCRIPT_DIR}/openrouter-responses-shim.mjs"
readonly SHIM_HOST="${OPENROUTER_SHIM_BIND_HOST:-127.0.0.1}"
readonly SHIM_PORT="${OPENROUTER_SHIM_PORT:-18082}"
readonly SHIM_UPSTREAM_BASE_URL="${OPENROUTER_SHIM_UPSTREAM_BASE_URL:-https://openrouter.ai}"
readonly CODEX_HOME_DIR="${HOME}/.codex"
readonly SHIM_PID_FILE="${CODEX_HOME_DIR}/openrouter-shim.pid"
readonly SHIM_LOG_FILE="${CODEX_HOME_DIR}/openrouter-shim.log"

is_shim_healthy() {
    curl -fsS "http://${SHIM_HOST}:${SHIM_PORT}/healthz" >/dev/null 2>&1
}

if ! command -v node >/dev/null 2>&1; then
    echo "Error: node is required to run OpenRouter compatibility shim." >&2
    exit 1
fi

if ! command -v curl >/dev/null 2>&1; then
    echo "Error: curl is required to run OpenRouter compatibility shim health checks." >&2
    exit 1
fi

if [ ! -f "${SHIM_SCRIPT}" ]; then
    echo "Error: OpenRouter shim script not found at '${SHIM_SCRIPT}'." >&2
    exit 1
fi

mkdir -p "${CODEX_HOME_DIR}"

if is_shim_healthy; then
    echo "OpenRouter compatibility shim already running on http://${SHIM_HOST}:${SHIM_PORT}."
    exit 0
fi

if [ -f "${SHIM_PID_FILE}" ]; then
    existing_pid="$(cat "${SHIM_PID_FILE}" 2>/dev/null || true)"
    if [ -n "${existing_pid}" ] && kill -0 "${existing_pid}" >/dev/null 2>&1; then
        kill "${existing_pid}" >/dev/null 2>&1 || true
    fi
    rm -f "${SHIM_PID_FILE}"
fi

OPENROUTER_SHIM_BIND_HOST="${SHIM_HOST}" \
OPENROUTER_SHIM_PORT="${SHIM_PORT}" \
OPENROUTER_SHIM_UPSTREAM_BASE_URL="${SHIM_UPSTREAM_BASE_URL}" \
nohup node "${SHIM_SCRIPT}" >>"${SHIM_LOG_FILE}" 2>&1 &

shim_pid="$!"
echo "${shim_pid}" > "${SHIM_PID_FILE}"

for _ in $(seq 1 50); do
    if is_shim_healthy; then
        echo "OpenRouter compatibility shim started on http://${SHIM_HOST}:${SHIM_PORT}."
        exit 0
    fi
    sleep 0.2
done

echo "Error: failed to start OpenRouter compatibility shim." >&2
if [ -f "${SHIM_LOG_FILE}" ]; then
    echo "Recent shim logs:" >&2
    tail -n 40 "${SHIM_LOG_FILE}" >&2 || true
fi
exit 1
