#!/usr/bin/env bash
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
    echo "FAIL: chạy script bằng root hoặc sudo." >&2
    exit 1
fi

if [ ! -r /etc/os-release ]; then
    echo "FAIL: không xác định được hệ điều hành." >&2
    exit 1
fi

. /etc/os-release
if [ "${ID:-}" != "ubuntu" ] || [ "${VERSION_ID:-}" != "24.04" ]; then
    echo "FAIL: baseline này chỉ được kiểm thử cho Ubuntu 24.04; hiện tại là ${PRETTY_NAME:-unknown}." >&2
    exit 1
fi

export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y ca-certificates curl rsync jq unattended-upgrades util-linux

install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
chmod a+r /etc/apt/keyrings/docker.asc

architecture="$(dpkg --print-architecture)"
codename="${UBUNTU_CODENAME:-$VERSION_CODENAME}"
docker_source=/etc/apt/sources.list.d/docker.sources
if [ ! -f "$docker_source" ]; then
    printf '%s\n' \
        'Types: deb' \
        'URIs: https://download.docker.com/linux/ubuntu' \
        "Suites: $codename" \
        'Components: stable' \
        "Architectures: $architecture" \
        'Signed-By: /etc/apt/keyrings/docker.asc' > "$docker_source"
fi

apt-get update
apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
systemctl enable --now docker

install -d -m 0755 /srv/phucthinhsolar
install -d -m 0700 /srv/phucthinhsolar/secrets /srv/phucthinhsolar/backups
install -d -m 0755 /srv/phucthinhsolar/releases /srv/phucthinhsolar/shared /srv/phucthinhsolar/state

echo "PASS: Docker và thư mục nền đã sẵn sàng."
echo "LƯU Ý: script không thay đổi SSH, Cloud Firewall hoặc DNS để tránh tự khóa quản trị."
