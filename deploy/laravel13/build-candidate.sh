#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"
env_file="${1:-runtime/stack.env}"
compose=(docker compose --env-file "$env_file")

if [[ -n "$(git status --porcelain --untracked-files=normal)" ]]; then
    echo 'Release build requires a clean Git worktree.' >&2
    exit 1
fi

source_revision="$(git rev-parse HEAD)"
short_revision="${source_revision:0:8}"
"${compose[@]}" config --quiet
mapfile -t images < <("${compose[@]}" config --format json | python3 -c '
import json, sys
config = json.load(sys.stdin)
services = config["services"]
backend, nuxt = services["backend"], services["nuxt"]
revision = backend["build"]["args"]["PTS_SOURCE_REVISION"]
assert revision == nuxt["build"]["args"]["PTS_SOURCE_REVISION"]
print(revision)
print(backend["image"])
print(nuxt["image"])
')

if [[ "${#images[@]}" -ne 3 || "${images[0]}" != "$source_revision" || "${images[1]}" != *":${short_revision}-candidate" || "${images[2]}" != *":${short_revision}-candidate" ]]; then
    echo 'Set PTS_SOURCE_REVISION to Git HEAD and both image tags to its first 8 characters.' >&2
    exit 1
fi

"${compose[@]}" build --quiet backend
"${compose[@]}" build --quiet nuxt

backend_id="$(docker image inspect --format '{{.Id}}' "${images[1]}")"
nuxt_id="$(docker image inspect --format '{{.Id}}' "${images[2]}")"
for image in "${images[1]}" "${images[2]}"; do
    revision="$(docker image inspect --format '{{index .Config.Labels "org.opencontainers.image.revision"}}' "$image")"
    if [[ "$revision" != "$source_revision" ]]; then
        echo "Image revision mismatch: $image" >&2
        exit 1
    fi
done

mkdir -p runtime
python3 - "$source_revision" "${images[1]}" "$backend_id" "${images[2]}" "$nuxt_id" > runtime/release-manifest.json <<'PY'
import json, sys
revision, backend, backend_id, nuxt, nuxt_id = sys.argv[1:]
print(json.dumps({"source_revision": revision, "backend": {"image": backend, "id": backend_id}, "nuxt": {"image": nuxt, "id": nuxt_id}}, indent=2))
PY
echo "Built $short_revision; image IDs recorded in runtime/release-manifest.json"
