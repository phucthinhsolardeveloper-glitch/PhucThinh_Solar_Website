import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isPublicCartPayload } from '../shared/contracts/public-cart.ts'

const item = {
  code: '0123456789abcdef0123456789abcdef',
  productId: 7,
  name: 'Sản phẩm thật',
  slug: 'san-pham-that',
  url: '/san-pham/san-pham-that',
  image: '/upload/product/real.webp',
  quantity: 2,
  color: '',
  size: '',
  pricing: { mode: 'price', currency: 'VND', regularPrice: 300000, salePrice: 250000 },
  unitPrice: 250000,
  lineTotal: 500000,
}
const good = { schemaVersion: 1, locale: 'vi', currency: 'VND', csrfToken: 'a'.repeat(64), count: 1, quantityCount: 2, items: [item], subtotal: 500000, canCheckout: true }

test('accepts a consistent session cart DTO', () => assert.equal(isPublicCartPayload(good), true))
test('rejects tampered totals, traversal and malformed session codes', () => {
  assert.equal(isPublicCartPayload({ ...good, subtotal: 1 }), false)
  assert.equal(isPublicCartPayload({ ...good, quantityCount: 3 }), false)
  assert.equal(isPublicCartPayload({ ...good, items: [{ ...item, image: '/upload/product/../secret.jpg' }] }), false)
  assert.equal(isPublicCartPayload({ ...good, items: [{ ...item, code: '../bad' }] }), false)
})
test('quote items disable checkout and hide monetary totals', () => {
  const quote = { ...item, pricing: { mode: 'quote', currency: 'VND', regularPrice: null, salePrice: null }, unitPrice: null, lineTotal: null }
  assert.equal(isPublicCartPayload({ ...good, items: [quote], subtotal: null, canCheckout: false }), true)
  assert.equal(isPublicCartPayload({ ...good, items: [quote], subtotal: 0, canCheckout: true }), false)
})
