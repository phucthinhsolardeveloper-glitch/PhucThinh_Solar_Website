import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { isPublicRouteResolveResult, isPublicRouteResolveSlug } from '../shared/contracts/public-route-resolve.ts'

const root = new URL('../', import.meta.url)
const read = (path) => readFileSync(new URL(path, root), 'utf8')

test('route classifier contract accepts only supported kinds with their exact canonical path shape', () => {
  const valid = [
    { schemaVersion: 1, slug: 'den-ufo-1000w', kind: 'pdp', canonicalPath: '/san-pham/den-ufo-1000w' },
    { schemaVersion: 1, slug: 'den-nlmt', kind: 'category', canonicalPath: '/danh-muc-san-pham/den-nlmt/den-pha/' },
    { schemaVersion: 1, slug: 'longi', kind: 'brand', canonicalPath: '/thuong-hieu/longi/' },
    { schemaVersion: 1, slug: 'album-du-an', kind: 'gallery', canonicalPath: '/album-du-an/' },
    { schemaVersion: 1, slug: 'kien-thuc-nang-luong', kind: 'news', canonicalPath: '/kien-thuc-nang-luong/' },
    { schemaVersion: 1, slug: 'tin-moi-nhat', kind: 'news-category', canonicalPath: '/tin-moi-nhat' },
    { schemaVersion: 1, slug: 'dien-mat-troi', kind: 'solution', canonicalPath: '/giai-phap/dien-mat-troi/' },
    { schemaVersion: 1, slug: 'ho-gia-dinh', kind: 'solution-category', canonicalPath: '/ho-gia-dinh' },
    { schemaVersion: 1, slug: 'du-an-mau', kind: 'project', canonicalPath: '/du-an-mau/' },
    { schemaVersion: 1, slug: 'du-an-ca-nhan', kind: 'project-category', canonicalPath: '/du-an-ca-nhan' },
    { schemaVersion: 1, slug: 'bao-hanh', kind: 'policy', canonicalPath: '/ho-tro/bao-hanh/' },
  ]
  for (const result of valid) assert.equal(isPublicRouteResolveResult(result), true, result.kind)
})

test('route classifier contract rejects malformed, mismatched, private, or guessed route data', () => {
  const invalid = [
    null,
    {},
    { schemaVersion: 1, slug: '../etc/passwd', kind: 'news', canonicalPath: '/../etc/passwd/' },
    { schemaVersion: 1, slug: 'den', kind: 'pdp', canonicalPath: '/san-pham/other' },
    { schemaVersion: 1, slug: 'den', kind: 'category', canonicalPath: '/danh-muc-san-pham/' },
    { schemaVersion: 1, slug: 'du-an-ca-nhan', kind: 'project-category', canonicalPath: '/du-an-ca-nhan/' },
    { schemaVersion: 1, slug: 'den', kind: 'brand', canonicalPath: '/den/' },
    { schemaVersion: 1, slug: 'video-item', kind: 'video', canonicalPath: '/video-item/' },
    { schemaVersion: 1, slug: 'static-page', kind: 'static', canonicalPath: '/static-page/' },
    { schemaVersion: 1, slug: 'hidden', kind: 'legacy-only', canonicalPath: null, reason: 'unsupported-taxonomy' },
    { schemaVersion: 1, slug: 'news', kind: 'news', canonicalPath: '/news/', id: 123 },
  ]
  for (const result of invalid) assert.equal(isPublicRouteResolveResult(result), false)
  for (const slug of ['../secret', 'a/b', '-bad', 'bad-', 'double--dash', 'Uppercase', ['slug'], '']) {
    assert.equal(isPublicRouteResolveSlug(slug), false)
  }
  assert.equal(isPublicRouteResolveSlug('safe-slug-123'), true)
})

test('Nitro adapter is GET-only, validates one slug, delegates to the fixed API endpoint, and disables caching', () => {
  const route = read('server/routes/_api/public-route-resolve.get.ts')
  assert.match(route, /requireGet\(event\)/)
  assert.match(route, /Object\.keys\(query\)\.some\(\(key\) => key !== 'slug'\)/)
  assert.match(route, /isPublicRouteResolveSlug\(query\.slug\)/)
  assert.match(route, /endpoint: '\/api\/v1\/public-route-resolve'/)
  assert.match(route, /passThroughStatuses: \[404\]/)
  assert.match(route, /Cache-Control', 'private, no-store'/)
  assert.match(route, /X-Robots-Tag', 'noindex, nofollow, noarchive'/)
  assert.doesNotMatch(route, /method:\s*'POST'|method:\s*'PUT'|method:\s*'DELETE'/)
})
