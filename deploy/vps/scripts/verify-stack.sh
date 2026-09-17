#!/usr/bin/env bash
set -euo pipefail

stack_root="${PTS_STACK_ROOT:-/srv/phucthinhsolar}"
cd "$stack_root"
set -a
. ./.env
set +a

docker compose config --quiet
docker compose ps

for image in $(docker compose config --images | sort -u); do
    digest="$(docker image inspect --format '{{join .RepoDigests " "}}' "$image" 2>/dev/null || true)"
    id="$(docker image inspect --format '{{.Id}}' "$image")"
    printf 'IMAGE %-36s %s %s\n' "$image" "$id" "$digest"
done

docker compose exec -T web php -r '
  $required=array("calendar","curl","exif","fileinfo","gd","json","mbstring","openssl","pdo_mysql","zip");
  $missing=array_values(array_filter($required,function($e){return !extension_loaded($e);}));
  if($missing){fwrite(STDERR,"Missing PHP extensions: ".implode(",",$missing)."\n");exit(1);}
  if(PHP_VERSION_ID<70400||PHP_VERSION_ID>=80000){fwrite(STDERR,"Unexpected PHP version\n");exit(1);}
  if(!function_exists("imagewebp")){fwrite(STDERR,"WebP encoder missing\n");exit(1);}
  echo "PHP runtime OK\n";
'

curl -fsS --max-time 15 "https://$SITE_DOMAIN/robots.txt" >/dev/null
curl -fsS --max-time 15 "https://$SITE_DOMAIN/" | grep -q '<title>'
echo "PASS: stack, runtime và HTTPS public đang hoạt động."
