import assert from 'node:assert/strict'
import { test } from 'node:test'
import {
  ABOUT_PUBLIC_PATH,
  SOLUTIONS_PUBLIC_PATH,
  CATALOG_PUBLIC_PATH,
  SEARCH_PUBLIC_PATH,
  CART_PUBLIC_PATH,
  ORDER_SUCCESS_PUBLIC_PATH,
  brandDetailPublicPath,
  isRuntimeFlagEnabled,
  phpPublicRouteUrl,
  productDetailPublicPath,
} from '../shared/public-route-gate.ts'

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

test('catalog and search static fallbacks resolve exactly', () => {
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', CATALOG_PUBLIC_PATH), 'http://127.0.0.1:8080/san-pham/')
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', SEARCH_PUBLIC_PATH), 'http://127.0.0.1:8080/tim-kiem/')
})

test('product detail slug builds the canonical no-trailing-slash path and rejects unsafe slugs', () => {
  assert.equal(productDetailPublicPath('den-ufo-1000w'), '/san-pham/den-ufo-1000w')
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', productDetailPublicPath('den-ufo-1000w')), 'http://127.0.0.1:8080/san-pham/den-ufo-1000w')
  assert.throws(() => productDetailPublicPath('../../etc/passwd'), /Invalid product slug/)
  assert.throws(() => productDetailPublicPath(''), /Invalid product slug/)
  assert.throws(() => productDetailPublicPath('<script>'), /Invalid product slug/)
})

test('brand detail slug builds the trailing-slash path and rejects unsafe slugs', () => {
  assert.equal(brandDetailPublicPath('longi'), '/thuong-hieu/longi/')
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', brandDetailPublicPath('longi')), 'http://127.0.0.1:8080/thuong-hieu/longi/')
  assert.throws(() => brandDetailPublicPath('../etc'), /Invalid brand slug/)
})

test('an unknown dynamic path still fails closed', () => {
  assert.throws(() => phpPublicRouteUrl('https://php.internal', '/san-pham/ok/extra'), /Unsupported public route fallback/)
  assert.throws(() => phpPublicRouteUrl('https://php.internal', '/thuong-hieu/ok'), /Unsupported public route fallback/)
})

test('cart static fallback resolves exactly', () => {
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', CART_PUBLIC_PATH), 'http://127.0.0.1:8080/gio-hang/')
})

test('order-success fallback forwards a non-empty token query and omits an empty one', () => {
  assert.equal(
    phpPublicRouteUrl('http://127.0.0.1:8080', ORDER_SUCCESS_PUBLIC_PATH, { token: 'abc123' }),
    'http://127.0.0.1:8080/cam-on/?token=abc123',
  )
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', ORDER_SUCCESS_PUBLIC_PATH, { token: '' }), 'http://127.0.0.1:8080/cam-on/')
  assert.equal(phpPublicRouteUrl('http://127.0.0.1:8080', ORDER_SUCCESS_PUBLIC_PATH), 'http://127.0.0.1:8080/cam-on/')
})
