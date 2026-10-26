#!/usr/bin/env bash
# Isolated, LOCAL-only PTS PHP + Nuxt preview. No production deployment or DB migration.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
FRONTEND="$ROOT/frontend"
ARTIFACTS="$FRONTEND/.migration-artifacts"
PHP_ROOT="$ROOT/domains/phucthinhsolar.com/public_html"
LEGACY_ROOT="${PTS_LEGACY_ROOT:-/home/admin01/src/Web_PTS_PHP}"
COMPOSE="$ROOT/docker-compose.nuxt-preview.yml"
PID_FILE="$ARTIFACTS/pts-nuxt-preview.pid"
LOG_FILE="$ARTIFACTS/pts-nuxt-preview.log"
mkdir -p "$ARTIFACTS"

stop_nuxt() {
  if [[ -f "$PID_FILE" ]]; then
    local pid
    pid="$(cat "$PID_FILE")"
    if [[ "$pid" =~ ^[0-9]+$ ]] && [[ -d "/proc/$pid" ]]; then
      local running_cwd running_cmd
      running_cwd="$(readlink -f "/proc/$pid/cwd" || true)"
      running_cmd="$(tr '\0' ' ' < "/proc/$pid/cmdline" || true)"
      if [[ "$running_cwd" == "$FRONTEND" && "$running_cmd" == *".output/server/index.mjs"* ]]; then
        kill "$pid"
        for _ in {1..25}; do
          [[ ! -d "/proc/$pid" ]] && break
          sleep 0.2
        done
      fi
    fi
    rm -f "$PID_FILE"
  fi
}

start_preview() {
  if [[ ! -f "$PHP_ROOT/libraries/config.php" ]]; then
    local local_config="$LEGACY_ROOT/domains/phucthinhsolar.com/public_html/libraries/config.php"
    if [[ ! -f "$local_config" ]]; then
      echo "Missing gitignored LOCAL PHP config: $local_config" >&2
      exit 1
    fi
    cp -p "$local_config" "$PHP_ROOT/libraries/config.php"
  fi

  mkdir -p "$PHP_ROOT/caches" "$PHP_ROOT/logs" "$PHP_ROOT/thumbs"
  docker compose -f "$COMPOSE" up -d

  if [[ ! -d "$FRONTEND/node_modules" ]]; then
    (cd "$FRONTEND" && npm ci)
  fi

  stop_nuxt
  (cd "$FRONTEND" && npm run build)

  python3 "$ROOT/scripts/nuxt-preview-launch.py"
  echo "PHP migration API: http://localhost:8088/api/pts_v1_site.php"
}

case "${1:-status}" in
  start|restart) start_preview ;;
  stop)
    stop_nuxt
    docker compose -f "$COMPOSE" stop web-preview
    echo "Stopped local preview only"
    ;;
  status)
    docker compose -f "$COMPOSE" ps web-preview
    if curl -fsS --max-time 2 -o /dev/null http://127.0.0.1:3100/migration-site-preview; then
      echo "Nuxt preview UP: http://localhost:3100/migration-site-preview"
    else
      echo "Nuxt preview is not running"
    fi
    ;;
  *)
    echo "Usage: scripts/nuxt-local-preview.sh {start|restart|stop|status}" >&2
    exit 2
    ;;
esac
