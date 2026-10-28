import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isPublicLeadFormSecurity, isPublicLeadFormResult } from '../shared/contracts/public-lead-form.ts'

const good = {
  schemaVersion: 1,
  csrfToken: 'a'.repeat(64),
  cities: [{ id: 1, name: 'Gia Lai' }],
  recaptcha: { active: true, siteKey: 'site_key_abcdefghijklmnopqrstuvwxyz' },
}
test('accepts lead form security metadata without exposing a secret key', () => assert.equal(isPublicLeadFormSecurity(good), true))
test('accepts disabled captcha and rejects malformed public metadata', () => {
  assert.equal(isPublicLeadFormSecurity({ ...good, recaptcha: { active: false, siteKey: '' } }), true)
  assert.equal(isPublicLeadFormSecurity({ ...good, csrfToken: 'bad' }), false)
  assert.equal(isPublicLeadFormSecurity({ ...good, cities: [{ id: 0, name: 'bad' }] }), false)
})
test('validates lead form success result', () => {
  assert.equal(isPublicLeadFormResult({ ok: true, message: 'Đã gửi thành công.' }), true)
  assert.equal(isPublicLeadFormResult({ ok: true, message: '' }), false)
})
