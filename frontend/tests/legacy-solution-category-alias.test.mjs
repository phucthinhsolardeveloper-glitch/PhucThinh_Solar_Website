import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'

const page = readFileSync(new URL('../app/pages/danh-muc-giai-phap/[slug].vue', import.meta.url), 'utf8')

test('legacy solution-category URLs redirect only when the PHP resolver confirms a category', () => {
  assert.match(page, /isPublicRouteResolveSlug\(slug\)/)
  assert.match(page, /\$fetch\('\/_migration\/public-route-resolve', \{ query: \{ slug \} \}\)/)
  assert.match(page, /resolution\.kind === 'solution-category'/)
  assert.match(page, /navigateTo\(\{ path: resolution\.canonicalPath, query: route\.query \}, \{ redirectCode: 301 \}\)/)
  assert.match(page, /setResponseStatus\(validSlug \? statusCode : 404/)
  assert.match(page, /robots: 'noindex, nofollow, noarchive'/)
})
