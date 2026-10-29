import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isPublicRouteResolution, normalizePublicRouteResolution } from '../shared/contracts/public-route-resolution.ts'

const base = { schemaVersion: 1, entity: 'news', slug: 'bai-viet-thu-nghiem', canonical: '/bai-viet-thu-nghiem/' }

test('accepts safe public route resolution DTOs', () => {
  assert.equal(isPublicRouteResolution(base), true)
  assert.equal(isPublicRouteResolution({ schemaVersion: 1, entity: 'not-found', slug: '', canonical: '' }), true)
  assert.equal(isPublicRouteResolution({ schemaVersion: 1, entity: 'news-category', slug: 'ung-dung', canonical: '/ung-dung/', category: { id: 6, slug: 'ung-dung', type: 'tin-tuc', level: 'item', path: 'kien-thuc/dien-mat-troi/ung-dung', name: 'Ứng dụng', description: '', image: '' } }), true)
  assert.equal(isPublicRouteResolution({ schemaVersion: 1, entity: 'project-category', slug: 'dien-mat-troi', canonical: '/dien-mat-troi/', category: { id: 9, slug: 'dien-mat-troi', type: 'du-an', level: 'cat', path: 'cong-trinh/dien-mat-troi', name: 'Điện mặt trời', description: '', image: '' } }), true)
  assert.equal(isPublicRouteResolution({ schemaVersion: 1, entity: 'solution-category', slug: 'ho-gia-dinh', canonical: '/ho-gia-dinh/', category: { id: 11, slug: 'ho-gia-dinh', type: 'giai-phap', level: 'list', path: 'ho-gia-dinh', name: 'Hộ gia đình', description: '', image: '' } }), true)
})

test('rejects unsupported entities, canonical mismatches, and inconsistent category chains', () => {
  assert.equal(isPublicRouteResolution({ ...base, entity: 'unknown' }), false)
  assert.equal(isPublicRouteResolution({ ...base, canonical: 'https://evil.test/bai-viet-thu-nghiem/' }), false)
  assert.equal(isPublicRouteResolution({ schemaVersion: 1, entity: 'news-category', slug: 'ung-dung', canonical: '/ung-dung/', category: { id: 6, slug: 'other', type: 'tin-tuc', level: 'item', path: 'kien-thuc/dien-mat-troi/ung-dung', name: 'Ứng dụng', description: '', image: '' } }), false)
})

test('normalizes the legacy QA resolver DTO at the bridge boundary', () => {
  assert.deepEqual(normalizePublicRouteResolution({ schemaVersion: 1, slug: 'bai-viet-thu-nghiem', kind: 'news', canonicalPath: '/bai-viet-thu-nghiem/' }), base)
  assert.deepEqual(normalizePublicRouteResolution({ schemaVersion: 1, slug: 'den-that', kind: 'pdp', canonicalPath: '/san-pham/den-that' }), { schemaVersion: 1, entity: 'product', slug: 'den-that', canonical: '/san-pham/den-that' })
  assert.equal(normalizePublicRouteResolution({ schemaVersion: 1, slug: 'x', kind: 'category', canonicalPath: '/x/' }), null)
  assert.equal(normalizePublicRouteResolution({ schemaVersion: 1, slug: 'x', kind: 'news', canonicalPath: 'https://evil.test/x/' }), null)
})
