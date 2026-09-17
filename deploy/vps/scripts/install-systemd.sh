#!/usr/bin/env bash
set -euo pipefail

source_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/../systemd" && pwd)"
[ "$(id -u)" -eq 0 ] || { echo "FAIL: chạy bằng root hoặc sudo." >&2; exit 1; }

install -m 0644 "$source_dir/phucthinhsolar-backup.service" /etc/systemd/system/phucthinhsolar-backup.service
install -m 0644 "$source_dir/phucthinhsolar-backup.timer" /etc/systemd/system/phucthinhsolar-backup.timer
install -m 0644 "$source_dir/phucthinhsolar-db-backup.service" /etc/systemd/system/phucthinhsolar-db-backup.service
install -m 0644 "$source_dir/phucthinhsolar-db-backup.timer" /etc/systemd/system/phucthinhsolar-db-backup.timer
systemctl daemon-reload
systemctl enable --now phucthinhsolar-backup.timer
systemctl enable --now phucthinhsolar-db-backup.timer
systemctl list-timers phucthinhsolar-backup.timer phucthinhsolar-db-backup.timer --no-pager
echo "PASS: backup timer đã được bật."
