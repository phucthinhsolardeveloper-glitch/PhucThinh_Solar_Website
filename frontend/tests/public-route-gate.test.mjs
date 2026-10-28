import assert from 'node:assert/strict'
import { test } from 'node:test'
import { ABOUT_PUBLIC_PATH, SOLUTIONS_PUBLIC_PATH, isRuntimeFlagEnabled, phpPublicRouteUrl } from '../shared/public-route-gate.ts'

test('public About route stays disabled unless the explicit runtime flag is enabled', () => {
  assert.equal(isRuntimeFlagEnabled(false), false)
  assert.equal(isRuntimeFlagEnabled(undefined), false)
  assert.equal(isRuntimeFlagEnabled('0'), false)
  assert.equal(isRuntimeFlagEnabled('true'), true)
  assert.equal(isRuntimeFlagEnabled(1), false)
})

test('flag-off fallback targets the configured PHP route exactly', () => {
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', ABOUT_PUBLIC_PATH), 'http://127.0.0.1:8080/gioi-thieu/')
  assert.equal(phpPublicRouteUrl('https://php.internal', ABOUT_PUBLIC_PATH), 'https://php.internal/gioi-thieu/')
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', SOLUTIONS_PUBLIC_PATH), 'http://127.0.0.1:8080/giai-phap/')
})

test('public route fallback rejects unsafe origins and paths', () => {
  assert.throws(() => phpPublicRouteUrl('javascript:alert(1)', ABOUT_PUBLIC_PATH), /Untrusted PHP public origin|Invalid URL/)
  assert.throws(() => phpPublicRouteUrl('https://user:pass@php.internal', ABOUT_PUBLIC_PATH), /Untrusted PHP public origin/)
  assert.throws(() => phpPublicRouteUrl('https://php.internal/path', ABOUT_PUBLIC_PATH), /Untrusted PHP public origin/)
  assert.throws(() => phpPublicRouteUrl('https://php.internal', '/admin/'), /Unsupported public route fallback/)
})
