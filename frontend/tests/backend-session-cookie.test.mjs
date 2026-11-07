import assert from 'node:assert/strict'
import { test } from 'node:test'
import { safeSessionCookie } from '../server/utils/backend-session.ts'

const value = 'eyJpdiI6IkFCQyIsInZhbHVlIjoiWFlaIn0%3D'

test('session cookie is Secure when Nuxt receives the request over HTTPS', () => {
  assert.equal(safeSessionCookie(`pts_session=${value}; path=/; HttpOnly`, true), `pts_session=${value}; Path=/; HttpOnly; SameSite=Lax; Secure`)
})

test('local HTTP preview keeps its session cookie usable without Secure', () => {
  assert.equal(safeSessionCookie(`pts_session=${value}; path=/; HttpOnly`, false), `pts_session=${value}; Path=/; HttpOnly; SameSite=Lax`)
})

test('upstream cookie attributes are replaced and other cookies dropped', () => {
  assert.equal(safeSessionCookie(`pts_session=${value}; domain=evil.example; path=/x`, false), `pts_session=${value}; Path=/; HttpOnly; SameSite=Lax`)
  assert.equal(safeSessionCookie('XSRF-TOKEN=abcdefghijklmnopq', true), null)
  assert.equal(safeSessionCookie('pts_session=short', true), null)
  assert.equal(safeSessionCookie('pts_session=has space in value', true), null)
})
