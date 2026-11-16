#!/usr/bin/env bash
set -euo pipefail

script_dir=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
tmp=$(mktemp -d)
trap 'rm -rf -- "$tmp"' EXIT
backups="$tmp/backups"
mkdir "$backups"

for day in 01 02 03 04; do
    mkdir "$backups/202611${day}T020000Z-full"
    touch -d "2026-11-${day} 02:00:00 UTC" "$backups/202611${day}T020000Z-full"
done
for day in 01 02 03 04 05 06 07 08 09 10; do
    mkdir "$backups/202611${day}T030000Z-db"
    touch -d "2026-11-${day} 03:00:00 UTC" "$backups/202611${day}T030000Z-db"
done
mkdir "$backups/unrelated-directory"

just_created=20261116T040000Z-full
mkdir "$backups/$just_created"
touch -d '2026-11-16 04:00:00 UTC' "$backups/$just_created"

"$script_dir/backup-retention.sh" "$backups" 7 2 "$just_created"

full_count=$(find "$backups" -mindepth 1 -maxdepth 1 -type d -name '20??????T??????Z-full' | wc -l)
[[ "$full_count" -eq 2 ]] || { echo "FAIL: expected 2 full backups, got $full_count" >&2; exit 1; }
[[ -d "$backups/20261104T020000Z-full" ]] || { echo "FAIL: newest existing full missing" >&2; exit 1; }
[[ -d "$backups/$just_created" ]] || { echo "FAIL: just-created full was removed" >&2; exit 1; }
for day in 09 10; do
    [[ -d "$backups/202611${day}T030000Z-db" ]] || { echo "FAIL: recent DB backup $day missing" >&2; exit 1; }
done
for day in 01 02; do
    [[ ! -e "$backups/202611${day}T030000Z-db" ]] || { echo "FAIL: expired DB backup $day remains" >&2; exit 1; }
done
db_count=$(find "$backups" -mindepth 1 -maxdepth 1 -type d -name '20??????T??????Z-db' | wc -l)
[[ "$db_count" -eq 2 ]] || { echo "FAIL: expected 2 in-retention DB backups, got $db_count" >&2; exit 1; }
[[ -d "$backups/unrelated-directory" ]] || { echo "FAIL: unrelated directory was removed" >&2; exit 1; }

"$script_dir/backup-retention.sh" "$backups" 7 0 "$just_created"
full_count=$(find "$backups" -mindepth 1 -maxdepth 1 -type d -name '20??????T??????Z-full' | wc -l)
[[ "$full_count" -ge 1 ]] || { echo "FAIL: BACKUP_KEEP_FULL below 1 removed every full backup" >&2; exit 1; }

echo "PASS: retention keeps 2 newest full backups (including the new backup), expires DB backups older than 7 days, and preserves unrelated directories."
