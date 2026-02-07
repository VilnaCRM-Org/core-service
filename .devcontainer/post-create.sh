#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

# Codespaces host Docker currently exposes API 1.43; newer clients need this pin.
export DOCKER_API_VERSION="${DOCKER_API_VERSION:-1.43}"

echo "Waiting for Docker daemon..."
for _ in $(seq 1 90); do
    if docker info >/dev/null 2>&1; then
        break
    fi
    sleep 2
done

docker info >/dev/null 2>&1 || {
    echo "Docker daemon is not available. Rebuild the Codespace and retry."
    exit 1
}

if ! command -v make >/dev/null 2>&1; then
    sudo apt-get update
    sudo apt-get install -y make
fi

if ! command -v codex >/dev/null 2>&1; then
    npm install -g @openai/codex
fi

if ! bash scripts/codespaces/setup-secure-agent-env.sh; then
    echo "Warning: secure agent bootstrap failed."
    echo "Set Codespaces secrets and rerun: bash scripts/codespaces/setup-secure-agent-env.sh"
fi

make start

if [ ! -f vendor/autoload.php ]; then
    make install
fi

echo "Codespace setup complete."
echo "Use 'make help' to list all available commands."
