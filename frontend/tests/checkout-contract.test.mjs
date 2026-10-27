import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isPublicCheckoutPayload, isPublicCheckoutWardsPayload, isPublicOrderThanksPayload } from '../shared/contracts/public-checkout.ts'

const token64 = 'a'.repeat(64)
const good = {
  schemaVersion: 1,
  locale: 'vi',
  csrfToken: token64,
  checkoutToken: 'b'.repeat(64),
  shippingEnabled: true,
  cartReady: true,
  cities: [{ id: 1, name: 'Gia Lai' }],
  payments: [
    { id: 3, name: 'Chuyển khoản', description: 'Theo hướng dẫn', code: 'legacy' },
    { id: -1, name: 'VNPay', description: '', code: 'vnpay' },
  ],
}

test('accepts checkout metadata with session tokens and visible options', () => assert.equal(isPublicCheckoutPayload(good), true))
test('rejects malformed tokens and mismatched payment code', () => {
  assert.equal(isPublicCheckoutPayload({ ...good, csrfToken: 'bad' }), false)
  assert.equal(isPublicCheckoutPayload({ ...good, payments: [{ id: -1, name: 'VNPay', description: '', code: 'legacy' }] }), false)
})
test('validates wards and session-bound thank-you DTO', () => {
  assert.equal(isPublicCheckoutWardsPayload({ schemaVersion: 1, locale: 'vi', cityId: 1, wards: [{ id: 2, name: 'Phường thật' }] }), true)
  assert.equal(isPublicCheckoutWardsPayload({ schemaVersion: 1, locale: 'vi', cityId: 0, wards: [] }), false)
  assert.equal(isPublicOrderThanksPayload({ schemaVersion: 1, locale: 'vi', order: { code: 'ABC123', customerName: 'Khách hàng', totalPrice: 500000 }, conversionPending: true }), true)
  assert.equal(isPublicOrderThanksPayload({ schemaVersion: 1, locale: 'vi', order: { code: '', customerName: 'Khách hàng', totalPrice: -1 }, conversionPending: false }), false)
})
