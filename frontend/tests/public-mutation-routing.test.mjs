import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { migrationCartErrorMessage } from '../app/composables/migrationCartError.ts'

const source = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')
const leadComposable = source('../app/composables/useLeadForm.ts')
const reviewComponent = source('../app/components/product-preview/ProductReviews.vue')
const leadPhp = source('../../domains/phucthinhsolar.com/public_html/api/pts_v1_lead_form.php')
const reviewPhp = source('../../domains/phucthinhsolar.com/public_html/api/pts_v1_review.php')
const cartComposable = source('../app/composables/useMigrationCart.ts')
const cartRoute = source('../server/routes/_migration/cart.post.ts')
const caddy = source('../../Caddyfile.local-full-nuxt')

test('rate-limited lead forms POST to the PHP API through the same-origin session', () => {
  assert.match(leadComposable, /\$fetch<unknown>\('\/api\/pts_v1_lead_form\.php'/)
  assert.match(leadComposable, /method: 'POST',[\s\S]*credentials: 'same-origin'/)
  assert.match(leadComposable, /csrfToken: meta\.csrfToken, recaptchaToken: token, data/)
  assert.match(leadPhp, /formCsrfValid/)
  assert.match(leadPhp, /formClientIp\(/)
  assert.match(leadPhp, /formPersistentRateLimit\(/)
})

test('product reviews POST to PHP and retain its JSON validation messages', () => {
  assert.match(reviewComponent, /\$fetch\('\/api\/pts_v1_review\.php'/)
  assert.match(reviewComponent, /credentials: 'same-origin'/)
  assert.match(reviewComponent, /csrfToken: props\.csrfToken, productId: props\.productId/)
  assert.match(reviewComponent, /reason\?\.data\?\.errors/)
  assert.match(reviewPhp, /formCsrfValid/)
  assert.match(reviewPhp, /formClientIp\(/)
  assert.match(reviewPhp, /formPersistentRateLimit\(/)
})

test('the Nuxt rehearsal Caddy sends same-origin PHP API requests to the PHP service', () => {
  assert.match(caddy, /@php_backend path \/api\/\*/)
  assert.match(caddy, /handle @php_backend \{\s*reverse_proxy host\.docker\.internal:8080/s)
  assert.doesNotMatch(caddy, /method POST[\s\S]{0,60}path \/api/)
})

test('cart error extraction preserves PHP object and string error contracts', () => {
  assert.equal(migrationCartErrorMessage({ data: { error: { message: 'Sản phẩm hiện nhận báo giá trực tiếp.' } } }), 'Sản phẩm hiện nhận báo giá trực tiếp.')
  assert.equal(migrationCartErrorMessage({ data: { error: 'Giỏ hàng không hợp lệ.' } }), 'Giỏ hàng không hợp lệ.')
  assert.equal(migrationCartErrorMessage({ data: { error: {}, statusMessage: 'Phiên giỏ hàng không hợp lệ.' } }), 'Phiên giỏ hàng không hợp lệ.')
  assert.equal(migrationCartErrorMessage({ data: { error: {} } }), 'Không thể cập nhật giỏ hàng.')
})

test('cart UI mutations still bridge to the PHP cart handler', () => {
  assert.match(cartComposable, /\$fetch\('\/_migration\/cart', \{ method: 'POST'/)
  assert.match(cartRoute, /fetchPhpSessionResponse\(event, '\/api\/pts_v1_cart_mutate\.php'/)
  assert.match(cartRoute, /body: JSON\.stringify\(payload\)/)
})
