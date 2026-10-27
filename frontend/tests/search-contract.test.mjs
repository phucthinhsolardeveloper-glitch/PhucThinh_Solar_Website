import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isPublicSearchPayload } from '../shared/contracts/public-search.ts'

const product = {
  id: 1,
  name: 'Inverter thật',
  slug: 'inverter-that',
  url: '/san-pham/inverter-that',
  media: ['/upload/product/inverter.webp'],
  powerWatts: 5000,
  pricing: { mode: 'price', currency: 'VND', regularPrice: 10000000, salePrice: null },
}
const good = { schemaVersion: 1, locale: 'vi', keyword: 'inverter', products: [product], pagination: { page: 1, perPage: 20, total: 1 } }

test('accepts search payload built from public catalog products', () => assert.equal(isPublicSearchPayload(good), true))
test('rejects unsafe keyword/product media and pagination', () => {
  assert.equal(isPublicSearchPayload({ ...good, keyword: 'x\u0000y' }), false)
  assert.equal(isPublicSearchPayload({ ...good, products: [{ ...product, media: ['/private/x.webp'] }] }), false)
  assert.equal(isPublicSearchPayload({ ...good, pagination: { page: 0, perPage: 20, total: 1 } }), false)
})
