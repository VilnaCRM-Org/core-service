#!/bin/sh
set -eu

required_files="
/srv/app/vendor/autoload.php
/srv/app/vendor/autoload_runtime.php
"

for file in $required_files; do
    until [ -f "$file" ]; do
        echo "Waiting for application bootstrap file: $file"
        sleep 1
    done
done

exec frankenphp run --config /etc/caddy/Caddyfile
