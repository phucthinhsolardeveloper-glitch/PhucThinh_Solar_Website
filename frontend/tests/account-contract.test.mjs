import assert from 'node:assert/strict'
import { test } from 'node:test'
import { isPublicAccountPayload } from '../shared/contracts/public-account.ts'

const valid = {
  schemaVersion: 1,
  action: 'orders',
  authenticated: true,
  csrfToken: 'a'.repeat(64),
  messages: [],
  fields: {},
  member: { fullname: 'Khách hàng', username: 'khachhang', gender: '1', birthday: '', email: 'user@example.test', phone: '0900000000', address: 'TP HCM' },
  orders: [{ id: 8, code: 'PTS-8', date: '2026-10-28 09:30:00', name: 'Khách hàng', phone: '0900000000', total: 420000, paid: false, status: 'Đang xử lý', returnRequested: false }],
  order: null,
  returns: [],
}

test('accepts account DTO with CSRF and return state', () => assert.equal(isPublicAccountPayload(valid), true))
test('rejects an account order missing its return-request state', () => {
  const payload = structuredClone(valid)
  delete payload.orders[0].returnRequested
  assert.equal(isPublicAccountPayload(payload), false)
})
test('rejects malformed CSRF and non-numeric order values', () => {
  assert.equal(isPublicAccountPayload({ ...valid, csrfToken: 'bad' }), false)
  assert.equal(isPublicAccountPayload({ ...valid, orders: [{ ...valid.orders[0], total: '420000' }] }), false)
})
