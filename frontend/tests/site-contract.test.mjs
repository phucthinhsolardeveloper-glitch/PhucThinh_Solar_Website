import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isPublicSitePayload } from '../shared/contracts/public-site.ts'

const good = {
  schemaVersion: 1,
  brand: { name: 'Phúc Thịnh Solar', hotline: '' },
  productCategories: [{ id: 4, name: 'Đèn', slug: 'den', url: '/danh-muc-san-pham/den/' }],
}

test('accepts published public site payload', () => {
  assert.equal(isPublicSitePayload(good), true)
})

test('rejects absent category identifiers', () => {
  assert.equal(isPublicSitePayload({ ...good, productCategories: [{ ...good.productCategories[0], id: 0 }] }), false)
})

test('rejects malformed API payload', () => {
  assert.equal(isPublicSitePayload({ schemaVersion: 1, brand: {}, productCategories: [] }), false)
})
