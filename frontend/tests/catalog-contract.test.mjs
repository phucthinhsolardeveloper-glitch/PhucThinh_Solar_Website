import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isCatalogSlugChain, isPublicCatalogPayload } from '../shared/contracts/public-catalog.ts'

const good = {
  schemaVersion: 1,
  locale: 'vi',
  category: [],
  categories: [{ id: 10, name: 'Điện mặt trời', slug: 'dien-mat-troi', url: '/danh-muc-san-pham/dien-mat-troi/' }],
  products: [{ id: 20, name: 'Sản phẩm thật từ CMS', slug: 'san-pham-that', url: '/san-pham/san-pham-that', media: ['/upload/product/photo.webp'], powerWatts: 650, pricing: { mode: 'price', currency: 'VND', regularPrice: 2648100, salePrice: null } }],
  pagination: { page: 1, perPage: 20, total: 1 },
  parity: {
    prices: { included: true },
    filters: { included: false, reason: 'legacy_filter_parity_not_verified' },
    sort: { included: false, reason: 'legacy_sort_parity_not_verified' },
  },
}

test('accepts the exact Phase A catalog DTO', () => assert.equal(isPublicCatalogPayload(good), true))
test('keeps the original v1 catalog payload valid during staggered integration', () => {
  const { powerWatts, pricing, ...legacyProduct } = good.products[0]
  const legacy = { ...good, products: [legacyProduct], parity: { ...good.parity, prices: { included: false, reason: 'public_pricing_authority_not_delegated' } } }
  assert.equal(isPublicCatalogPayload(legacy), true)
})
test('rejects private fields, unsafe media and invalid pagination', () => {
  assert.equal(isPublicCatalogPayload({ ...good, products: [{ ...good.products[0], price: 100 }] }), false)
  assert.equal(isPublicCatalogPayload({ ...good, products: [{ ...good.products[0], media: ['/upload/product/../secret.php'] }] }), false)
  assert.equal(isPublicCatalogPayload({ ...good, pagination: { ...good.pagination, perPage: 49 } }), false)
  assert.equal(isPublicCatalogPayload({ ...good, pagination: { ...good.pagination, page: 10001 } }), false)
  assert.equal(isPublicCatalogPayload({ ...good, pagination: { ...good.pagination, page: 10000 } }), true)
})
test('accepts quote-only products without inventing a price', () => {
  const quote = { ...good.products[0], powerWatts: null, pricing: { mode: 'quote', currency: 'VND', regularPrice: null, salePrice: null } }
  assert.equal(isPublicCatalogPayload({ ...good, products: [quote] }), true)
  assert.equal(isPublicCatalogPayload({ ...good, products: [{ ...quote, pricing: { ...quote.pricing, regularPrice: 1 } }] }), false)
})
test('accepts category chain only at the supported 0..4 slug boundaries', () => {
  assert.equal(isCatalogSlugChain(''), true)
  assert.equal(isCatalogSlugChain('dien-mat-troi'), true)
  assert.equal(isCatalogSlugChain('a/b/c/d'), true)
  assert.equal(isCatalogSlugChain('a/b/c/d/e'), false)
  assert.equal(isCatalogSlugChain('A/b'), false)
  assert.equal(isCatalogSlugChain('a//b'), false)
})
test('accepts a resolved category object with its canonical full path', () => {
  assert.equal(isPublicCatalogPayload({ ...good, category: { id: 11, name: 'Đèn', slug: 'den', url: '/danh-muc-san-pham/dien-mat-troi/den/' } }), true)
})
