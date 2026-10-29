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
const filtered = {
  ...good,
  filters: {
    brands: [{ id: 7, name: 'LONGi' }],
    priceRanges: [{ key: '2', label: '1.000.000đ - 3.000.000đ' }],
  },
  selected: { brandIds: [7], priceKeys: ['2'], sort: 'default' },
}

test('accepts search payload built from public catalog products', () => assert.equal(isPublicSearchPayload(good), true))
test('accepts allowlisted brand and public price filters', () => assert.equal(isPublicSearchPayload(filtered), true))
test('rejects unsafe keyword/product media and pagination', () => {
  assert.equal(isPublicSearchPayload({ ...good, keyword: 'x\u0000y' }), false)
  assert.equal(isPublicSearchPayload({ ...good, products: [{ ...product, media: ['/private/x.webp'] }] }), false)
  assert.equal(isPublicSearchPayload({ ...good, pagination: { page: 0, perPage: 20, total: 1 } }), false)
})
test('rejects malformed search filter options and selected state', () => {
  assert.equal(isPublicSearchPayload({ ...filtered, filters: { ...filtered.filters, brands: [{ id: 0, name: 'LONGi' }] } }), false)
  assert.equal(isPublicSearchPayload({ ...filtered, filters: { ...filtered.filters, priceRanges: [{ key: '4', label: 'Giá' }] } }), false)
  assert.equal(isPublicSearchPayload({ ...filtered, selected: { brandIds: [1.5], priceKeys: [] } }), false)
  assert.equal(isPublicSearchPayload({ ...filtered, selected: { ...filtered.selected, sort: 'raw-sql' } }), false)
  assert.equal(isPublicSearchPayload({ ...good, filters: filtered.filters }), false)
})
