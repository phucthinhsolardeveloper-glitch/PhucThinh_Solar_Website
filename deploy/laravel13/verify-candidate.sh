#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"
env_file="${1:-runtime/stack.env}"
manifest="${2:-runtime/release-manifest.json}"
compose=(docker compose --env-file "$env_file")

"${compose[@]}" config --quiet
[[ -f "$manifest" ]] || { echo 'Missing release manifest.' >&2; exit 1; }
"${compose[@]}" config --format json | python3 -c '
import json, re, sys
from urllib.parse import urlparse
config = json.load(sys.stdin)
services = config["services"]
backend, db, proxy = (services[name] for name in ("backend", "db", "proxy"))
env = backend["environment"]
assert env["DB_HOST"] == "db", "DB must stay inside the candidate stack"
assert re.search(r"(?:candidate|staging|rehearsal)", env["DB_DATABASE"]), "DB name is not isolated"
assert len(env.get("APP_KEY", "")) >= 32, "APP_KEY is missing"
assert env.get("APP_TIMEZONE") == "Asia/Ho_Chi_Minh" and env.get("APP_LOCALE") == "vi", "Locale or timezone drift"
assert env.get("APP_DEBUG") == "false" and env.get("APP_ENV") == "production", "Unsafe Laravel runtime flags"
assert env.get("SESSION_DRIVER") == "database" and env.get("CACHE_STORE") == "database", "Session or cache contract drift"
assert env.get("UPLOAD_PATH") == "/srv/upload" and env.get("ASSETS_PATH") == "/srv/assets", "Media paths differ from mounts"
url = urlparse(env.get("APP_URL", ""))
assert url.scheme in ("http", "https") and url.hostname, "APP_URL is invalid"
if url.scheme == "https":
    assert env.get("SESSION_SECURE_COOKIE") == "true", "HTTPS requires secure session cookies"
if env.get("MAIL_MAILER") == "smtp":
    assert all(env.get(key) for key in ("MAIL_HOST", "MAIL_PORT", "MAIL_FROM_ADDRESS", "MAIL_ADMIN_TO")), "SMTP contract incomplete"
if env.get("VNPAY_ACTIVE") in ("1", "true"):
    assert all(env.get(key) for key in ("VNPAY_TMN_CODE", "VNPAY_HASH_SECRET", "VNPAY_RETURN_URL", "VNPAY_IPN_URL")), "VNPay contract incomplete"
assert not db.get("ports"), "Candidate DB must not publish a port"
assert len(proxy["ports"]) == 1 and proxy["ports"][0]["host_ip"] == "127.0.0.1", "Candidate proxy must bind loopback"
print("Isolated DB and loopback proxy: OK")
'

mapfile -t release < <(python3 - "$manifest" <<'PY'
import json, sys
data = json.load(open(sys.argv[1]))
print(data["source_revision"])
for service in ("backend", "nuxt"):
    print(data[service]["image"])
    print(data[service]["id"])
PY
)
[[ "${#release[@]}" -eq 5 && "${release[0]}" =~ ^[0-9a-f]{40}$ ]] || { echo 'Invalid release manifest.' >&2; exit 1; }
mapfile -t configured < <("${compose[@]}" config --format json | python3 -c '
import json, sys
config = json.load(sys.stdin)
print(config["services"]["backend"]["build"]["args"]["PTS_SOURCE_REVISION"])
print(config["services"]["backend"]["image"])
print(config["services"]["nuxt"]["image"])
')
if [[ "${#configured[@]}" -ne 3 || "${configured[0]}" != "${release[0]}" || "${configured[1]}" != "${release[1]}" || "${configured[2]}" != "${release[3]}" || "$(git rev-parse HEAD)" != "${release[0]}" ]]; then
    echo 'Checkout, Compose images and release manifest disagree.' >&2
    exit 1
fi

for service in backend nuxt; do
    if [[ "$service" == backend ]]; then index=1; else index=3; fi
    image="${release[$index]}"
    expected_id="${release[$((index+1))]}"
    actual_id="$(docker image inspect --format '{{.Id}}' "$image")"
    revision="$(docker image inspect --format '{{index .Config.Labels "org.opencontainers.image.revision"}}' "$image")"
    if [[ "$actual_id" != "$expected_id" || "$revision" != "${release[0]}" ]]; then
        echo "Image ID or source revision mismatch: $image" >&2
        exit 1
    fi
    container_id="$("${compose[@]}" ps -q "$service")"
    running_id="$(docker inspect --format '{{.Image}}' "$container_id")"
    if [[ "$running_id" != "$expected_id" ]]; then
        echo "Running $service container does not use the release image ID." >&2
        exit 1
    fi
done

"${compose[@]}" ps --format json | python3 -c '
import json, sys
rows = [json.loads(line) for line in sys.stdin if line.strip()]
for service in ("db", "backend", "nuxt"):
    matches = [row for row in rows if row["Service"] == service]
    assert len(matches) == 1 and matches[0]["State"] == "running" and matches[0]["Health"] == "healthy", f"{service} is not healthy"
print("DB, backend and Nuxt: healthy")
'

"${compose[@]}" exec -T -u www-data backend sh -c 'test -w /srv/upload && test -w /var/www/html/storage'
echo 'Upload and app storage writable by www-data'

status="$("${compose[@]}" exec -T backend php artisan migrate:status --no-ansi)"
if [[ "$status" != *' Ran'* || "$status" == *' Pending'* ]]; then
    echo 'Database has no applied migrations or still has pending migrations.' >&2
    exit 1
fi
echo 'Database migrations: all applied'

"${compose[@]}" exec -T backend php artisan pts:snapshot

port="$("${compose[@]}" config --format json | python3 -c 'import json,sys; print(json.load(sys.stdin)["services"]["proxy"]["ports"][0]["published"])')"
for path in / /health /api/v1/site /admin/login /san-pham/ /gio-hang/ /thanh-toan/ /cam-on/ /sitemap.xml /robots.txt; do
    code="$(curl -sS -o /dev/null -w '%{http_code}' "http://127.0.0.1:${port}${path}")"
    if [[ "$code" != 200 ]]; then
        echo "Route $path returned $code" >&2
        exit 1
    fi
done
if ! curl -fsSI "http://127.0.0.1:${port}/" | tr -d '\r' | grep -qi '^x-robots-tag:.*noindex'; then
    echo 'Candidate route is missing noindex.' >&2
    exit 1
fi
echo 'Candidate routes and noindex: OK'
