#!/usr/bin/env bash
set -euo pipefail

stack_root="${PTS_STACK_ROOT:-/srv/phucthinhsolar}"
[ "$(id -u)" -eq 0 ] || { echo "FAIL: chạy bằng root hoặc sudo." >&2; exit 1; }
[ -d "$stack_root/state" ] && [ -f "$stack_root/.env" ] || { echo "FAIL: chưa chạy install-stack.sh." >&2; exit 1; }
for command in docker flock sed; do
    command -v "$command" >/dev/null 2>&1 || { echo "FAIL: thiếu lệnh $command." >&2; exit 1; }
done
exec 9>"$stack_root/state/operation.lock"
flock -n 9 || { echo "BLOCK: một thao tác deploy/backup/restore khác đang chạy." >&2; exit 1; }
previous_file="$stack_root/state/previous-image"
[ -s "$previous_file" ] || { echo "FAIL: chưa có previous-image." >&2; exit 1; }
previous_image="$(cat "$previous_file")"
docker image inspect "$previous_image" >/dev/null

current_file="$stack_root/state/current-image"
current_image="$(cat "$current_file" 2>/dev/null || true)"

previous_nuxt_file="$stack_root/state/previous-nuxt-image"
current_nuxt_file="$stack_root/state/current-nuxt-image"
previous_nuxt_image="$(cat "$previous_nuxt_file" 2>/dev/null || true)"
current_nuxt_image="$(cat "$current_nuxt_file" 2>/dev/null || true)"
if [ -n "$previous_nuxt_image" ]; then
    docker image inspect "$previous_nuxt_image" >/dev/null
fi

sed -i "s#^PTS_WEB_IMAGE=.*#PTS_WEB_IMAGE=$previous_image#" "$stack_root/.env"
if [ -n "$previous_nuxt_image" ] && grep -q '^PTS_NUXT_IMAGE=' "$stack_root/.env"; then
    sed -i "s#^PTS_NUXT_IMAGE=.*#PTS_NUXT_IMAGE=$previous_nuxt_image#" "$stack_root/.env"
fi
if grep -q '^PTS_NUXT_HYBRID_ENABLED=' "$stack_root/.env"; then
    sed -i "s#^PTS_NUXT_HYBRID_ENABLED=.*#PTS_NUXT_HYBRID_ENABLED=0#" "$stack_root/.env"
fi

cd "$stack_root"
if grep -q '^  nuxt:' "$stack_root/docker-compose.yml"; then
    docker compose up -d web nuxt proxy
else
    docker compose up -d web proxy
fi

printf '%s\n' "$previous_image" > "$current_file"
[ -z "$current_image" ] || printf '%s\n' "$current_image" > "$previous_file"
if [ -n "$previous_nuxt_image" ]; then
    printf '%s\n' "$previous_nuxt_image" > "$current_nuxt_file"
    [ -z "$current_nuxt_image" ] || printf '%s\n' "$current_nuxt_image" > "$previous_nuxt_file"
fi
echo "PASS: đã rollback code về $previous_image${previous_nuxt_image:+ và $previous_nuxt_image}. Database/upload không bị thay đổi."
