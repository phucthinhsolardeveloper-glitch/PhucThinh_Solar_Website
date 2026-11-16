#!/usr/bin/env bash
# Retention policy for finalized PTS Laravel backups. Names are deliberately
# constrained so unrelated directories in the shared VPS are never selected.
set -euo pipefail

if [ "$#" -lt 3 ] || [ "$#" -gt 4 ]; then
    echo "Usage: $0 BACKUPS_DIR RETENTION_DAYS KEEP_FULL [JUST_CREATED]" >&2
    exit 2
fi

backups_dir=$1
retention_days=$2
keep_full=$3
just_created=${4:-}

[ -d "$backups_dir" ] || { echo "FAIL: backup directory missing: $backups_dir" >&2; exit 1; }

if ! [[ "$keep_full" =~ ^[0-9]+$ ]]; then
    echo "WARN: BACKUP_KEEP_FULL không hợp lệ; dùng mặc định 2." >&2
    keep_full=2
elif [ "$keep_full" -lt 1 ]; then
    echo "WARN: BACKUP_KEEP_FULL tối thiểu là 1; dùng 1." >&2
    keep_full=1
fi

if ! [[ "$retention_days" =~ ^[0-9]+$ ]] || [ "$retention_days" -lt 3 ]; then
    echo "WARN: BACKUP_RETENTION_DAYS không hợp lệ; không xoay backup DB." >&2
else
    while IFS= read -r path; do
        name=${path##*/}
        [ "$name" = "$just_created" ] && continue
        echo "Remove expired DB backup: $path"
        rm -rf -- "$path"
    done < <(find "$backups_dir" -mindepth 1 -maxdepth 1 -type d \
        -name '20??????T??????Z-db' -mtime "+$retention_days" -print)
fi

mapfile -t full_backups < <(find "$backups_dir" -mindepth 1 -maxdepth 1 -type d \
    -name '20??????T??????Z-full' -printf '%f\n' | LC_ALL=C sort -r)

kept=0
for name in "${full_backups[@]}"; do
    if [ "$name" = "$just_created" ] || [ "$kept" -lt "$keep_full" ]; then
        echo "Keep full backup: $backups_dir/$name"
        kept=$((kept + 1))
    else
        echo "Remove old full backup: $backups_dir/$name"
        rm -rf -- "$backups_dir/$name"
    fi
done
