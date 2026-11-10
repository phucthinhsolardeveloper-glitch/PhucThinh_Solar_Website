#!/usr/bin/env bash
# Prepares the local MariaDB container after its first start (empty volume): creates the app database, the test
# database and the app user named in backend/.env, and restores a dump when one is given.
#   bash scripts/maintenance/local-db-init.sh                  # empty databases (then: php artisan migrate)
#   bash scripts/maintenance/local-db-init.sh /path/dump.sql   # restore a dump made with mariadb-dump --databases
# Safe to run again: it only creates what is missing. Needs the container `pts-mariadb-local` to be healthy.
set -euo pipefail
cd "$(dirname "$0")/../.."
DK="${DOCKER:-docker}"
env_value() { grep -E "^$1=" backend/.env | head -1 | cut -d= -f2- | sed -E 's/^"(.*)"$/\1/'; }
NAME=$(env_value DB_DATABASE); USER=$(env_value DB_USERNAME); PASS=$(env_value DB_PASSWORD)
: "${NAME:=pts_app}"; : "${USER:=pts_laravel}"
TMP=$(mktemp)
cat > "$TMP" <<SQL
CREATE DATABASE IF NOT EXISTS \`${NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS \`pts_app_test\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${USER}'@'%' IDENTIFIED BY '${PASS}';
GRANT ALL ON \`${NAME}\`.* TO '${USER}'@'%';
GRANT ALL ON \`pts_app_test\`.* TO '${USER}'@'%';
FLUSH PRIVILEGES;
SQL
# docker.exe under WSL does not pass stdin: copy the file in, then run it.
run_sql() { $DK cp "$1" pts-mariadb-local:/tmp/init.sql < /dev/null && $DK exec pts-mariadb-local sh -c 'mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" --default-character-set=utf8mb4 < /tmp/init.sql; rm -f /tmp/init.sql' < /dev/null; }
run_sql "$TMP"; rm -f "$TMP"
echo "databases and user ready"
if [ -n "${1:-}" ]; then run_sql "$1"; echo "dump restored: $1"; fi
