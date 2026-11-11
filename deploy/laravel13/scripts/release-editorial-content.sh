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
    echo "BLOCK: Verify fresh production DB/upload backup first, then set PTS_EDITORIAL_BACKUP_VERIFIED=1." >&2
    exit 1
  fi
  echo "Applying packaged editorial content as hidden drafts (no automatic publication)"
  docker exec "$container" php artisan pts:editorial-batch --apply
  echo "Verifying idempotent repeat preview:"
  docker exec "$container" php artisan pts:editorial-batch
else
  echo "Preview only. After backup verification: PTS_EDITORIAL_BACKUP_VERIFIED=1 bash $0 --apply"
fi
