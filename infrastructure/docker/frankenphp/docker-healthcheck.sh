#!/bin/sh
set -eu

curl -fsS --connect-timeout 2 --max-time 5 http://127.0.0.1/api/health >/dev/null
