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

const extended = {
  ...good,
  categoryHero: null,
  categoryContent: { title: '', applications: [], faq: [] },
  support: null,
  filters: {
    brands: [{ id: 5, name: 'LONGI' }, { id: 6, name: 'ASTRONERGY' }],
    priceRanges: [{ key: '1', label: 'Dưới 1.000.000đ' }, { key: '2', label: '1.000.000đ - 3.000.000đ' }, { key: '3', label: 'Trên 3.000.000đ' }],
    subcategories: [{ id: 7, name: 'Kẹp tấm pin', slug: 'kep-tam-pin' }],
  },
  selected: { brandIds: [5], priceKeys: ['2'], subcategoryIds: [], sort: 'default' },
  parity: { ...good.parity, filters: { included: true }, sort: { included: true } },
}

test('accepts the extended shape with real brand/price/subcategory filters and sort', () => {
  assert.equal(isPublicCatalogPayload(extended), true)
})
test('rejects a sort key or price key outside the real backend whitelist', () => {
  assert.equal(isPublicCatalogPayload({ ...extended, selected: { ...extended.selected, sort: 'relevance' } }), false)
  assert.equal(isPublicCatalogPayload({ ...extended, selected: { ...extended.selected, priceKeys: ['4'] } }), false)
  assert.equal(isPublicCatalogPayload({ ...extended, filters: { ...extended.filters, priceRanges: [{ key: '9', label: 'x' }] } }), false)
})
test('rejects an extended payload missing filters or selected (not a valid staggered shape)', () => {
  const { selected: _selected, ...withoutSelected } = extended
  assert.equal(isPublicCatalogPayload(withoutSelected), false)
})
test('rejects invented IDs that are not safe positive integers in selected filters', () => {
  assert.equal(isPublicCatalogPayload({ ...extended, selected: { ...extended.selected, brandIds: [-1] } }), false)
  assert.equal(isPublicCatalogPayload({ ...extended, selected: { ...extended.selected, brandIds: [1.5] } }), false)
})
