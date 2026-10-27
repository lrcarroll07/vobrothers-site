#!/usr/bin/env bash
# Serves the local site at http://localhost:8080 (override with PORT=xxxx).
cd "$(dirname "$0")/.."
PORT="${PORT:-8080}"
export WP_HOME="http://localhost:$PORT"
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"
exec php -S "localhost:$PORT" -t wp bin/router.php
