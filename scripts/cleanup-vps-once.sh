#!/usr/bin/env bash
# Dọn rác + backup thừa trên VPS production, giữ đúng 1 bản backup full +
# 1 bản backup db-only gần nhất (thay vì chờ hết hạn retention 14 ngày mặc
# định trong deploy/vps/scripts/backup.sh). KHÔNG đụng shared/upload, KHÔNG
# đụng database đang chạy.
#
# Chạy: ssh -i ~/.ssh/id_ed25519 root@222.255.182.182 'bash -s' < scripts/cleanup-vps-once.sh
set -euo pipefail
stack_root=/srv/phucthinhsolar
cd "$stack_root"

echo "=== Disk trước khi dọn ==="
df -h /
du -sh "$stack_root"/* 2>/dev/null | sort -h

echo
echo "=== Xóa page cache đã sinh lại được ==="
rm -fv shared/caches/cache_* 2>/dev/null || true
find shared/thumbs -mindepth 1 -type f -delete 2>/dev/null || true
find shared/assets-caches -mindepth 1 -type f -delete 2>/dev/null || true

echo
echo "=== Docker image prune (chỉ image dangling, không đụng image đang chạy/rollback) ==="
docker image prune -f

echo
echo "=== Backup hiện có (trước khi dọn) ==="
ls -1 backups/ 2>/dev/null || echo "(không có thư mục backups/)"

latest_full=$(find backups -maxdepth 1 -mindepth 1 -type d -name '*-full' -printf '%f\n' 2>/dev/null | sort | tail -1)
latest_db=$(find backups -maxdepth 1 -mindepth 1 -type d -name '*-db' -printf '%f\n' 2>/dev/null | sort | tail -1)

echo
echo "Giữ lại: full=${latest_full:-<không có>}  db=${latest_db:-<không có>}"
for dir in backups/*/; do
    name="$(basename "$dir")"
    [ "$name" = "$latest_full" ] && continue
    [ "$name" = "$latest_db" ] && continue
    echo "Xóa backup cũ: $name"
    rm -rf -- "backups/$name"
done

echo
echo "=== Disk sau khi dọn ==="
df -h /
du -sh "$stack_root"/* 2>/dev/null | sort -h
echo
echo "=== Backup còn lại ==="
ls -lh backups/ 2>/dev/null || true
