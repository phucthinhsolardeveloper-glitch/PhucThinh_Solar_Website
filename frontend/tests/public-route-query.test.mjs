import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('editorial route middleware is always Nuxt-owned and retains canonical query state', () => {
  const files = ['news', 'projects', 'gallery', 'video']
  for (const name of files) {
    const source = read(`../app/middleware/${name}-public-route.ts`)
    assert.doesNotMatch(source, /PublicRouteEnabled|phpPublicRouteUrl|redirectCode: 307/)
    assert.match(source, /query: to\.query/)
  }
})
