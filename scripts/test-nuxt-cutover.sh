#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$repo_root"

bash -n scripts/test-nuxt-cutover.sh
node --test scripts/tests/nuxt-cutover.test.mjs
node scripts/nuxt-cutover-preflight.mjs

output="$(node scripts/nuxt-cutover-preflight.mjs --rollback-plan)"
grep -F 'GENERATED ONLY' <<<"$output" >/dev/null
grep -F 'docker compose config --quiet' <<<"$output" >/dev/null

if node scripts/nuxt-cutover-preflight.mjs --probe \
  --php-origin https://example.test --nuxt-origin http://127.0.0.1:3100 >/dev/null 2>&1; then
  echo 'FAIL: remote origin was accepted' >&2
  exit 1
fi

echo 'PASS: Nuxt cutover dry-run guards'
