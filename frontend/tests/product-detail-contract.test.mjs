import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isProductSlug, isPublicProductDetailPayload } from '../shared/contracts/public-product-detail.ts'

const good = { schemaVersion: 1, locale: 'vi', product: { id: 7, name: 'Đèn thật', slug: 'den-that', url: '/san-pham/den-that', code: 'PT-7', summary: '', profile: 'lighting', media: ['/upload/product/real.webp'], specifications: [{ key: 'cong_suat', label: 'Công suất', value: '30W' }] }, pricing: { mode: 'price', currency: 'VND', regularPrice: 2648100, salePrice: null }, content: { rendered: false, reason: 'product_content_blocks_not_wired' }, canonical: { url: '/san-pham/den-that', sources: { product: 'publicProductEntityPath(product)' } }, parity: { quote: { included: false, reason: 'legacy_quote_flow_stays_php' } } }

test('validates product detail DTO', () => assert.equal(isPublicProductDetailPayload(good), true))
test('validates exact slug and rejects traversal/media leakage', () => {
  assert.equal(isProductSlug('den-that'), true)
  assert.equal(isProductSlug('Den That'), false)
  assert.equal(isPublicProductDetailPayload({ ...good, product: { ...good.product, media: ['/upload/product/../secret.jpg'] } }), false)
})
test('rejects a product canonical URL that carries a trailing slash (PHP router has none for /san-pham/{slug})', () => {
  assert.equal(isPublicProductDetailPayload({ ...good, product: { ...good.product, url: '/san-pham/den-that/' } }), false)
  assert.equal(isPublicProductDetailPayload({ ...good, product: { ...good.product, url: '/san-pham/other' } }), false)
})
test('accepts quote-only pricing without inventing a price, rejects a price on a quote', () => {
  const quote = { mode: 'quote', currency: 'VND', regularPrice: null, salePrice: null }
  assert.equal(isPublicProductDetailPayload({ ...good, pricing: quote }), true)
  assert.equal(isPublicProductDetailPayload({ ...good, pricing: { ...quote, regularPrice: 1 } }), false)
})
test('rejects malformed or invented pricing shapes', () => {
  assert.equal(isPublicProductDetailPayload({ ...good, pricing: { included: true, reason: 'x', price: 1 } }), false)
  assert.equal(isPublicProductDetailPayload({ ...good, pricing: { ...good.pricing, currency: 'USD' } }), false)
})
