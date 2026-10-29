import assert from 'node:assert/strict'
import { test } from 'node:test'
import { safePhpSessionCookie } from '../server/utils/migration-cart.ts'

test('PHP session cookie is Secure when Nuxt receives the request over HTTPS', () => {
  assert.equal(
    safePhpSessionCookie('PHPSESSID=abcdefghijklmnop; path=/; HttpOnly', true),
    'PHPSESSID=abcdefghijklmnop; Path=/; HttpOnly; SameSite=Lax; Secure',
  )
})

test('local HTTP preview keeps its session cookie usable without Secure', () => {
  assert.equal(
    safePhpSessionCookie('PHPSESSID=abcdefghijklmnop; path=/; HttpOnly', false),
    'PHPSESSID=abcdefghijklmnop; Path=/; HttpOnly; SameSite=Lax',
  )
})

test('PHP session cookie rejects unrelated names and malformed identifiers', () => {
  assert.equal(safePhpSessionCookie('OTHER=abcdefghijklmnop', true), null)
  assert.equal(safePhpSessionCookie('PHPSESSID=short', true), null)
  assert.equal(safePhpSessionCookie('PHPSESSID=has space in id', true), null)
})
