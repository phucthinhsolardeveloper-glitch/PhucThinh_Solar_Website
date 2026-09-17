#!/usr/bin/env bash
set -euo pipefail

stack_root="${PTS_STACK_ROOT:-/srv/phucthinhsolar}"
[ "$(id -u)" -eq 0 ] || { echo "FAIL: chạy bằng root hoặc sudo." >&2; exit 1; }
cd "$stack_root"

timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
target="$stack_root/backups/runtime-images-$timestamp.tar.gz"
mapfile -t images < <(docker compose config --images | sort -u)
[ "${#images[@]}" -gt 0 ] || { echo "FAIL: compose không có image." >&2; exit 1; }
for image in "${images[@]}"; do docker image inspect "$image" >/dev/null; done

umask 077
docker save "${images[@]}" | gzip -1 > "$target"
sha256sum "$target" > "$target.sha256"
sha256sum -c "$target.sha256"
echo "PASS: runtime image bundle tại $target"
echo "Đây là bản bảo hiểm cho PHP 7.4 EOL; phải sao chép ra nơi lưu off-site."
