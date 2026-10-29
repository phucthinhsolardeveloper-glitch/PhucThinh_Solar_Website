import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const page = readFileSync(new URL('../app/pages/migration-checkout-preview.vue', import.meta.url), 'utf8')
const postRoute = readFileSync(new URL('../server/routes/_migration/checkout.post.ts', import.meta.url), 'utf8')
const cartPage = readFileSync(new URL('../app/components/cart/CartPage.vue', import.meta.url), 'utf8')

test('checkout preview is read-only and does not collect order details or submit an order', () => {
  assert.match(page, /Preview chỉ đọc/)
  assert.match(page, /không tạo đơn hàng/)
  assert.match(page, /useFetch<unknown>\('\/_migration\/cart'/)
  assert.doesNotMatch(page, /_migration\/checkout['"].*method:\s*['"]POST|submitOrder|checkoutToken|dataOrder/)
  assert.doesNotMatch(page, /<input\b|<select\b|<textarea\b|type="submit"/)
})

test('migration checkout POST endpoint refuses to forward order mutations to PHP', () => {
  assert.match(postRoute, /statusCode:\s*410/)
  assert.match(postRoute, /Checkout is disabled in migration preview/)
  assert.doesNotMatch(postRoute, /fetchPhpSessionResponse|migrationPhpEndpoint|thanhtoan|dataOrder/)
})

test('cart preview does not present an action that implies a real checkout', () => {
  assert.doesNotMatch(cartPage, /migration-checkout-preview|Tiếp tục thanh toán/)
  assert.match(cartPage, /Checkout đang ở chế độ preview chỉ đọc/)
  assert.match(cartPage, /không tạo đơn hàng/)
})
