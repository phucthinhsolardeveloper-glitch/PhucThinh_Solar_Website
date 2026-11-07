import assert from 'node:assert/strict'
import { test } from 'node:test'
import { isAllowedApiOrigin } from '../server/utils/backend-api.ts'

const allowlist = '127.0.0.1:8080,127.0.0.1:8088,localhost:8080,localhost:8088,pts-php81-staging'

test('accepts exact local and explicitly configured service origins', () => {
  assert.equal(isAllowedApiOrigin(new URL('http://127.0.0.1:8088'), allowlist), true)
  assert.equal(isAllowedApiOrigin(new URL('http://pts-php81-staging'), allowlist), true)
})

test('rejects SSRF targets outside the origin allowlist', () => {
  for (const value of [
    'http://169.254.169.254',
    'http://metadata.google.internal',
    'http://evil.example:8088',
    'http://pts-php81-staging.evil.example',
    'http://127.0.0.1:9000',
  ]) {
    assert.equal(isAllowedApiOrigin(new URL(value), allowlist), false, value)
  }
})

test('rejects wildcard, credential and path-shaped allowlist entries', () => {
  assert.equal(isAllowedApiOrigin(new URL('http://evil.example'), '*'), false)
  assert.equal(isAllowedApiOrigin(new URL('http://evil.example'), 'evil.example/path'), false)
  assert.equal(isAllowedApiOrigin(new URL('http://evil.example'), 'evil.example'), true)
  assert.equal(isAllowedApiOrigin(new URL('http://evil.example'), 'evil.example:8080'), false)
  assert.equal(isAllowedApiOrigin(new URL('http://evil.example'), 'user:pass@evil.example'), false)
})

test('supports default HTTPS port only when the hostname is explicitly configured', () => {
  assert.equal(isAllowedApiOrigin(new URL('https://php.internal'), 'php.internal'), true)
  assert.equal(isAllowedApiOrigin(new URL('https://php.internal:8443'), 'php.internal'), false)
})
