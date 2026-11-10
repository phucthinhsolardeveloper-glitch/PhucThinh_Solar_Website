import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('cart mutation re-reads the session token and retries once on 419', () => {
  const cart = read('../app/composables/useShopCart.ts')
  assert.match(cart, /statusCode !== 419\) throw reason[\s\S]*current = await refresh\(\)[\s\S]*csrfToken: current\.csrfToken/)
})

test('cart reads are ordered against concurrent refreshes and mutations', async () => {
  const { isCurrentCartRead } = await import('../shared/cart-refresh-guard.ts')
  assert.equal(isCurrentCartRead(4, 4, 8, 8), true)
  assert.equal(isCurrentCartRead(4, 5, 8, 8), false, 'a read started before a mutation must be discarded')
  assert.equal(isCurrentCartRead(4, 4, 8, 9), false, 'an older refresh must not overwrite the newer one')
  const cart = read('../app/composables/useShopCart.ts')
  assert.match(cart, /pendingRequests\.value > 0/)
  assert.match(cart, /cartRevision\.value \+= 1[\s\S]*beginRequest\(\)/)
  assert.match(cart, /cartRevision\.value \+= 1\n\s*const after = await refresh\(\)[\s\S]*return after/)
})

test('add to cart plays the flight to the header cart and a dismissible confirmation card', () => {
  const panel = read('../app/components/product-detail/ProductPurchasePanel.vue')
  const feedback = read('../app/components/commerce/CartFeedback.vue')
  const header = read('../app/components/SiteHeader.vue')
  const app = read('../app/app.vue')
  assert.match(panel, /celebrate\(\{ name: props\.productName/)
  assert.doesNotMatch(panel, /Đã thêm sản phẩm vào giỏ hàng\./)
  assert.match(header, /data-cart-target/)
  assert.match(app, /<CartFeedback \/>/)
  assert.match(feedback, /prefers-reduced-motion: reduce/)
  assert.match(feedback, /aria-live="polite"/)
  assert.match(feedback, /e\.key === 'Escape'/)
  assert.match(feedback, /@mouseenter="pause"[\s\S]*@focusin="pause"/)
})
