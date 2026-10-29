import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const root = new URL('../', import.meta.url)
const read = (path) => readFileSync(new URL(path, root), 'utf8')

test('public cart API failure cannot render as a successful HTTP response', () => {
  const page = read('app/components/cart/CartPage.vue')
  assert.match(page, /initialCartError/)
  assert.match(page, /!isMigrationPreview\.value && import\.meta\.server/)
  assert.match(page, /setResponseStatus\(502, 'Bad Gateway'\)/)
})

test('public order confirmation validates token and preserves not-found/upstream status', () => {
  const middleware = read('app/middleware/order-success-public-route.ts')
  const page = read('app/components/order/OrderThanksPage.vue')
  assert.match(middleware, /orderSuccessPublicRouteEnabled/)
  assert.match(middleware, /if \(isRuntimeFlagEnabled\(config\.public\.orderSuccessPublicRouteEnabled\)\) return/)
  assert.doesNotMatch(middleware, /throw createError\(\{ statusCode: 404/)
  assert.match(page, /errorStatus === 404/)
  assert.match(page, /const hasValidToken = computed\(\(\) => \/\^\[a-f0-9\]\{48\}\$\//)
  assert.match(page, /hasValidToken\.value \? \$fetch\('\/_migration\/order-thanks'/)
  assert.match(page, /errorStatus === 404 \|\| !hasValidToken\.value/)
  assert.match(page, /setResponseStatus\(404, 'Not Found'\)/)
  assert.match(page, /setResponseStatus\(502, 'Bad Gateway'\)/)
  assert.match(page, /conversionPending/)
  assert.match(page, /event: 'order_success'/)
  assert.match(page, /trackedConversions\.has\(currentToken\)/)
  assert.match(page, /Tạm thời không thể xác nhận đơn hàng/)
  assert.match(page, /Liên kết có thể đã hết hạn/)
  assert.match(read('app/pages/cam-on/index.vue'), /robots: 'noindex, nofollow, noarchive'/)
})

test('public product detail preserves not-found and upstream errors and enforces its no-slash canonical path', () => {
  const page = read('app/pages/san-pham/[slug].vue')
  const middleware = read('app/middleware/product-detail-public-route.ts')
  assert.match(page, /productErrorStatus === 404\) setResponseStatus\(404, 'Not Found'\)/)
  assert.match(page, /else if \(import\.meta\.server && \(error\.value \|\| !product\.value\)\) setResponseStatus\(502, 'Bad Gateway'\)/)
  assert.match(middleware, /to\.path !== targetPath\) return navigateTo\(targetPath, \{ redirectCode: 301 \}\)/)
  assert.match(middleware, /Product not found/)
})

test('public brand route validates slug and redirects to its canonical trailing-slash path', () => {
  const middleware = read('app/middleware/brand-detail-public-route.ts')
  assert.match(middleware, /Brand not found/)
  assert.match(middleware, /to\.path !== targetPath\) return navigateTo\(targetPath, \{ redirectCode: 301 \}\)/)
})
