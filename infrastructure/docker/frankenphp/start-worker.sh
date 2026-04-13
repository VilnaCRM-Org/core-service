#!/bin/sh
set -eu

required_files="
/srv/app/vendor/autoload.php
/srv/app/vendor/autoload_runtime.php
"
max_wait_seconds=${FRANKENPHP_BOOTSTRAP_MAX_WAIT_SECONDS:-60}

case "$max_wait_seconds" in
    ''|*[!0-9]*)
        echo "FRANKENPHP_BOOTSTRAP_MAX_WAIT_SECONDS must be a non-negative integer." >&2
        exit 1
        ;;
esac

for file in $required_files; do
    elapsed_seconds=0

    while [ ! -f "$file" ]; do
        if [ "$elapsed_seconds" -ge "$max_wait_seconds" ]; then
            echo "Timed out after ${max_wait_seconds}s waiting for application bootstrap file: $file" >&2
            exit 1
        fi

        echo "Waiting for application bootstrap file: $file"
        sleep 1
        elapsed_seconds=$((elapsed_seconds + 1))
    done
done

exec frankenphp run --config /etc/caddy/Caddyfile
