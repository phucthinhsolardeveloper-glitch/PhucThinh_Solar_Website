import { test } from 'node:test'
import assert from 'node:assert/strict'
import { MIGRATION_PREVIEW_ROUTES } from '../shared/contracts/migration-preview-routes.ts'
import { phpHref, previewHref, resolvePreviewLink } from '../shared/contracts/migration-preview-routes.ts'

test('registry is deterministic and contains only confirmed preview routes', () => {
  assert.deepEqual(MIGRATION_PREVIEW_ROUTES.map(({ key }) => key), ['home', 'catalog', 'product', 'search', 'cart', 'checkout', 'solutions', 'projects', 'news', 'about', 'contact', 'dealer', 'shell'])
  assert.equal(new Set(MIGRATION_PREVIEW_ROUTES.map(({ previewPath }) => previewPath)).size, MIGRATION_PREVIEW_ROUTES.length)
  assert.ok(MIGRATION_PREVIEW_ROUTES.every(({ previewPath }) => /^\/migration-[a-z-]+-preview$/.test(previewPath)))
})

test('preview mapping never takes over a public route', () => {
  assert.equal(previewHref('home'), '/migration-home-preview')
  assert.equal(previewHref('product', { slug: 'san-pham-that' }), '/migration-product-preview?slug=san-pham-that')
  assert.equal(resolvePreviewLink('solutions'), '/migration-solutions-preview')
  assert.throws(() => previewHref('product', { slug: '../secret' }))
})

test('PHP fallback is explicit localhost only', () => {
  assert.equal(phpHref('contact'), 'http://localhost:8088/lien-he/')
  assert.throws(() => phpHref('home', 'https://production.example'))
  assert.throws(() => phpHref('home', 'http://localhost:8088.evil.test'))
  assert.throws(() => resolvePreviewLink('home', 'unknown'))
})

test('checkout registry clearly describes the preview as read-only with order submission disabled', () => {
  const checkout = MIGRATION_PREVIEW_ROUTES.find(({ key }) => key === 'checkout')
  assert.ok(checkout)
  assert.match(checkout.description, /chỉ xem tóm tắt giỏ hàng/)
  assert.match(checkout.description, /không thu thập thông tin hay gửi đơn/)
  assert.match(checkout.description, /POST checkout bị khóa \(410\)/)
  assert.doesNotMatch(checkout.description, /order mutation vẫn chạy transaction/i)
})

