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

sed -i "s#^PTS_WEB_IMAGE=.*#PTS_WEB_IMAGE=$previous_image#" "$stack_root/.env"
cd "$stack_root"
docker compose up -d web proxy
echo "PASS: đã rollback code về $previous_image. Database/upload không bị thay đổi."
