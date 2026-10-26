import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isProductSlug, isPublicProductDetailPayload } from '../shared/contracts/public-product-detail.ts'

const good = { schemaVersion: 1, locale: 'vi', product: { id: 7, name: 'Đèn thật', slug: 'den-that', url: '/san-pham/den-that/', code: 'PT-7', summary: '', profile: 'lighting', media: ['/upload/product/real.webp'], specifications: [{ key: 'cong_suat', label: 'Công suất', value: '30W' }] }, pricing: { included: false, reason: 'public_pricing_authority_not_delegated' }, content: { rendered: false, reason: 'ckeditor_content_deferred_until_sanitizer_gate' }, canonical: { url: '/san-pham/den-that/', sources: { product: 'publicProductEntityPath(product)' } }, parity: { quote: { included: false, reason: 'legacy_quote_flow_stays_php' } } }

test('validates product detail DTO', () => assert.equal(isPublicProductDetailPayload(good), true))
test('validates exact slug and rejects traversal/media leakage', () => {
  assert.equal(isProductSlug('den-that'), true)
  assert.equal(isProductSlug('Den That'), false)
  assert.equal(isPublicProductDetailPayload({ ...good, product: { ...good.product, media: ['/upload/product/../secret.jpg'] } }), false)
})
test('rejects invented pricing and malformed canonical URL', () => {
  assert.equal(isPublicProductDetailPayload({ ...good, pricing: { included: true, reason: 'x', price: 1 } }), false)
  assert.equal(isPublicProductDetailPayload({ ...good, product: { ...good.product, url: '/san-pham/other/' } }), false)
})
