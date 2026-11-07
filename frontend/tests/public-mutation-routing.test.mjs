import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { cartErrorMessage } from '../app/composables/cartError.ts'

const source = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')
const leadComposable = source('../app/composables/useLeadForm.ts')
const reviewComponent = source('../app/components/product-detail/ProductReviews.vue')
const leadApi = source('../../backend/app/Domain/Leads/LeadSubmission.php')
const reviewApi = source('../../backend/app/Domain/Reviews/ReviewSubmission.php')
const cartComposable = source('../app/composables/useShopCart.ts')
const cartRoute = source('../server/routes/_api/cart.post.ts')
const caddy = source('../../Caddyfile.local-full-nuxt')

test('rate-limited lead forms POST straight to the API so the visitor IP reaches the rate limit', () => {
  assert.match(leadComposable, /\$fetch<unknown>\('\/api\/v1\/lead-form'/)
  assert.match(leadComposable, /method: 'POST',[\s\S]*credentials: 'same-origin'/)
  assert.match(leadComposable, /csrfToken: meta\.csrfToken, recaptchaToken: token, data/)
  assert.match(leadApi, /FormToken::valid/)
  assert.match(leadApi, /RateLimiter::tooManyAttempts/)
})

test('product reviews POST to the API and keep its JSON validation messages', () => {
  assert.match(reviewComponent, /\$fetch\('\/api\/v1\/review'/)
  assert.match(reviewComponent, /credentials: 'same-origin'/)
  assert.match(reviewComponent, /csrfToken: props\.csrfToken, productId: props\.productId/)
  assert.match(reviewComponent, /reason\?\.data\?\.errors/)
  assert.match(reviewApi, /FormToken::valid/)
  assert.match(reviewApi, /RateLimiter::tooManyAttempts/)
})

test('the Nuxt rehearsal Caddy sends same-origin API requests to Laravel', () => {
  assert.match(caddy, /@api path \/api\/\*/)
  assert.match(caddy, /handle @api \{\s*reverse_proxy host\.docker\.internal:8090/s)
})

test('cart error extraction preserves API object and string error contracts', () => {
  assert.equal(cartErrorMessage({ data: { error: { message: 'Sản phẩm hiện nhận báo giá trực tiếp.' } } }), 'Sản phẩm hiện nhận báo giá trực tiếp.')
  assert.equal(cartErrorMessage({ data: { error: 'Giỏ hàng không hợp lệ.' } }), 'Giỏ hàng không hợp lệ.')
  assert.equal(cartErrorMessage({ data: { error: {}, statusMessage: 'Phiên giỏ hàng không hợp lệ.' } }), 'Phiên giỏ hàng không hợp lệ.')
  assert.equal(cartErrorMessage({ data: { error: {} } }), 'Không thể cập nhật giỏ hàng.')
})

test('cart UI mutations bridge to the API cart with the session cookie', () => {
  assert.match(cartComposable, /\$fetch\('\/_api\/cart', \{ method: 'POST'/)
  assert.match(cartRoute, /fetchSessionResponse\(event, '\/api\/v1\/cart'/)
  assert.match(cartRoute, /body: JSON\.stringify\(payload\)/)
})
