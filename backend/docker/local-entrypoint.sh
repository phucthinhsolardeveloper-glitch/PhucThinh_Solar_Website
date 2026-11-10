#!/usr/bin/env bash
# Local development container: MariaDB (UTC, like production) and the Laravel server in ONE container.
#
#  - First start (application database missing): creates the DB user/databases named in /app/.env (DB_USERNAME, DB_PASSWORD,
#    DB_DATABASE) plus pts_app_test for the test suite, and restores /seed/local-dbs.sql when that file was copied in.
#  - Later starts reuse the data volume.
#  - On stop, MariaDB is shut down cleanly before the container exits.
set -euo pipefail

DATADIR=/var/lib/mysql
ENV_FILE=/app/.env
env_value() { grep -E "^$1=" "$ENV_FILE" | head -1 | cut -d= -f2- | sed -E 's/^"(.*)"$/\1/; s/^'"'"'(.*)'"'"'$/\1/'; }

mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld
# The image ships an initialised (empty) data dir; a brand-new volume inherits it, so "first start" is decided below by
# whether the application database exists, not by the data dir.
[ -d "$DATADIR/mysql" ] || mariadb-install-db --user=mysql --datadir="$DATADIR" --skip-test-db > /dev/null

mariadbd --user=mysql --datadir="$DATADIR" --bind-address=127.0.0.1 --skip-name-resolve \
  --default-time-zone=+00:00 --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci \
  --innodb-buffer-pool-size=256M --max-connections=60 > /var/log/mariadbd.log 2>&1 &
DBPID=$!

for _ in $(seq 1 60); do mariadb-admin ping --silent 2> /dev/null && break; sleep 1; done
mariadb-admin ping --silent 2> /dev/null || { echo "MariaDB did not start"; tail -20 /var/log/mariadbd.log; exit 1; }

DB_NAME=$(env_value DB_DATABASE); DB_USER=$(env_value DB_USERNAME); DB_PASS=$(env_value DB_PASSWORD)
: "${DB_NAME:=pts_app}"; : "${DB_USER:=pts_laravel}"
if [ -z "$(mariadb -N -e "SHOW DATABASES LIKE '${DB_NAME}'")" ]; then
  mariadb <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS \`pts_app_test\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
GRANT ALL ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost', '${DB_USER}'@'127.0.0.1';
GRANT ALL ON \`pts_app_test\`.* TO '${DB_USER}'@'localhost', '${DB_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
  if [ -f /seed/local-dbs.sql ]; then
    echo "Restoring /seed/local-dbs.sql ..."
    mariadb --default-character-set=utf8mb4 < /seed/local-dbs.sql
    echo "Restored."
  fi
fi

stop() { kill -TERM "${PHPPID:-0}" 2> /dev/null || true; mariadb-admin shutdown 2> /dev/null || kill -TERM "$DBPID" 2> /dev/null || true; wait "$DBPID" 2> /dev/null || true; exit 0; }
trap stop TERM INT

cd /app
php artisan serve --host=0.0.0.0 --port=8000 &
PHPPID=$!
wait "$PHPPID"
stop
