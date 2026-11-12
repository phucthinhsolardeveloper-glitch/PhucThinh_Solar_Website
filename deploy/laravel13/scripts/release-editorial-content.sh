#!/usr/bin/env bash
set -euo pipefail
# Content and media are a versioned, idempotent step of the Laravel/Nuxt release.
# This is not the legacy deploy/vps PHP 8.1 pipeline.
container="${PTS_EDITORIAL_BACKEND_CONTAINER:-pts-laravel13-backend}"
mode="${1:---preview}"

case "$mode" in
  --preview|--apply) ;;
  *) echo "Usage: $0 [--preview|--apply]" >&2; exit 2 ;;
esac

command -v docker >/dev/null || { echo "docker not found" >&2; exit 1; }
status="$(docker inspect -f '{{.State.Running}}' "$container" 2>/dev/null || true)"
[[ "$status" == true ]] || { echo "BLOCK: Backend container $container is not running" >&2; exit 1; }

echo "Editorial release preflight in container: $container"
docker exec "$container" php artisan pts:editorial-batch

if [[ "$mode" == --apply ]]; then
  if [[ "${PTS_EDITORIAL_BACKUP_VERIFIED:-0}" != 1 ]]; then
    echo "BLOCK: Verify fresh DB/upload backup before importing content, then set PTS_EDITORIAL_BACKUP_VERIFIED=1." >&2
    exit 1
  fi
  echo "Running the isolated editorial planner migration (not unrelated migrations)."
  docker exec "$container" php artisan migrate --path=database/migrations/2026_11_12_000100_create_editorial_plan_items_table.php --force
  echo "Importing 9 hidden drafts and 180 planning slots (no automatic publication)."
  docker exec "$container" php artisan pts:editorial-batch --apply
  docker exec "$container" php artisan pts:editorial-calendar --apply
  echo "Read-only repeat / idempotence checks:"
  docker exec "$container" php artisan pts:editorial-batch
  docker exec "$container" php artisan pts:editorial-calendar
else
  if ! docker exec "$container" php artisan pts:editorial-calendar; then
    echo "Planner table may be pending; apply mode migrates this one table AFTER backup."
  fi
  echo "Preview only. After backup verification: PTS_EDITORIAL_BACKUP_VERIFIED=1 bash $0 --apply"
fi
