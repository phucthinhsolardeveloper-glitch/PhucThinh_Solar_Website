import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const page = readFileSync(new URL('../app/pages/migration-checkout-preview.vue', import.meta.url), 'utf8')
const postRoute = readFileSync(new URL('../server/routes/_migration/checkout.post.ts', import.meta.url), 'utf8')
const cartPage = readFileSync(new URL('../app/components/cart/CartPage.vue', import.meta.url), 'utf8')
const activeCheckoutPage = readFileSync(new URL('../app/pages/thanh-toan.vue', import.meta.url), 'utf8')
const checkoutMiddleware = readFileSync(new URL('../app/middleware/checkout-public-route.ts', import.meta.url), 'utf8')
const caddy = readFileSync(new URL('../../Caddyfile.local-full-nuxt', import.meta.url), 'utf8')
const nuxtConfig = readFileSync(new URL('../nuxt.config.ts', import.meta.url), 'utf8')
const publicRouteGate = readFileSync(new URL('../shared/public-route-gate.ts', import.meta.url), 'utf8')
const compose = readFileSync(new URL('../../docker-compose.nuxt-service.yml', import.meta.url), 'utf8')
const phpNoticeHelper = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/libraries/pts_public_checkout_notice.php', import.meta.url), 'utf8')
const phpNoticeApi = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/api/pts_v1_checkout_notice.php', import.meta.url), 'utf8')
const siteHeader = readFileSync(new URL('../app/components/SiteHeader.vue', import.meta.url), 'utf8')

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

test('active Nuxt checkout hands the browser form directly to the PHP cart route', () => {
  assert.match(activeCheckoutPage, /middleware:\s*checkoutPublicRoute/)
  assert.match(activeCheckoutPage, /<form v-else method="post" :action="checkoutPostAction"/)
  assert.match(activeCheckoutPage, /const checkoutPostAction = CART_CHECKOUT_POST_PATH/)
  assert.doesNotMatch(activeCheckoutPage, /phpPublicRouteUrl|phpPublicOrigin/)
  assert.match(publicRouteGate, /CART_CHECKOUT_POST_PATH = '\/gio-hang'/)
  assert.doesNotMatch(publicRouteGate, /CART_CHECKOUT_POST_PATH = '\/gio-hang\//)
  for (const name of [
    'form_csrf',
    'checkout_token',
    'dataOrder[fullname]',
    'dataOrder[phone]',
    'dataOrder[email]',
    'dataOrder[city]',
    'dataOrder[ward]',
    'dataOrder[address]',
    'dataOrder[requirements]',
    'dataOrder[payments]',
  ]) assert.match(activeCheckoutPage, new RegExp(`name="${name.replace(/[\[\]]/g, '\\$&')}"`))
  assert.match(activeCheckoutPage, /name="thanhtoan" value="1"/)
  assert.doesNotMatch(activeCheckoutPage, /fetch\(['"]\/_migration\/checkout['"].*method:\s*['"]POST|submitOrder/)
})

test('Nuxt checkout and cart are always available without route feature flags', () => {
  assert.doesNotMatch(nuxtConfig, /checkoutPublicRouteEnabled/)
  assert.doesNotMatch(compose, /NUXT_PUBLIC_CHECKOUT_PUBLIC_ROUTE_ENABLED/)
  assert.doesNotMatch(checkoutMiddleware, /PublicRouteEnabled|phpPublicRouteUrl/)
  assert.match(cartPage, /v-else\s+to="\/thanh-toan\/"/)
  assert.match(cartPage, /!payload\.canCheckout/)
})

test('local hybrid Caddy sends only checkout POST on /gio-hang to PHP', () => {
  assert.match(caddy, /@php_form_post\s*\{\s*method POST\s*path \/gio-hang \/gio-hang\/\*/)
  assert.match(caddy, /handle @php_form_post\s*\{\s*reverse_proxy host\.docker\.internal:8080\s*\}/)
})

test('PHP checkout flash is read once through a separate route without racing the header cart refresh', () => {
  assert.match(phpNoticeApi, /ptsPublicCheckoutNoticePayload\(\$_SESSION\)/)
  assert.match(phpNoticeHelper, /unset\(\$session\['flash'\]\[\$key\]\)/)
  assert.match(phpNoticeHelper, /'message', 'toast'/)
  assert.match(cartPage, /onMounted\(async \(\) => \{[\s\S]*?_migration\/checkout-notice/)
  assert.match(cartPage, /role="alert"/)
  assert.match(cartPage, /openCheckout[\s\S]*checkoutDraft\.value = checkoutNotice\.value\?\.form/)
  assert.doesNotMatch(siteHeader, /checkout-notice/)
})

test('cart preview does not present an action that implies a real checkout', () => {
  assert.doesNotMatch(cartPage, /migration-checkout-preview|Tiếp tục thanh toán/)
  assert.match(cartPage, /Checkout đang ở chế độ preview chỉ đọc/)
  assert.match(cartPage, /không tạo đơn hàng/)
})
