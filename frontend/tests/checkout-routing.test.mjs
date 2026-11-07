import { test } from 'node:test'
import assert from 'node:assert/strict'
import { existsSync, readFileSync } from 'node:fs'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')
const checkoutPage = read('../app/pages/thanh-toan.vue')
const cartPage = read('../app/components/cart/CartPage.vue')
const routeGate = read('../shared/public-route-gate.ts')
const siteHeader = read('../app/components/SiteHeader.vue')
const caddy = read('../../Caddyfile.local-full-nuxt')
const shop = read('../../backend/app/Domain/Commerce/Shop.php')
const shopController = read('../../backend/app/Http/Controllers/Api/V1/ShopController.php')

test('the checkout form posts straight to the API checkout', () => {
  assert.match(routeGate, /CART_CHECKOUT_POST_PATH = '\/api\/v1\/checkout'/)
  assert.match(checkoutPage, /<form v-else method="post" :action="checkoutPostAction"/)
  for (const name of ['form_csrf', 'checkout_token', 'dataOrder[fullname]', 'dataOrder[phone]', 'dataOrder[email]', 'dataOrder[city]', 'dataOrder[ward]', 'dataOrder[address]', 'dataOrder[requirements]', 'dataOrder[payments]']) {
    assert.match(checkoutPage, new RegExp(`name="${name.replace(/[\[\]]/g, '\\$&')}"`))
  }
  assert.match(shopController, /post\('dataOrder'\)/)
  assert.match(shopController, /redirect\(\$shop->formRedirect\(\$result\), 303\)/)
})

test('there is no Nuxt-side proxy for order, lead or review posts', () => {
  for (const name of ['checkout', 'lead-form', 'review']) assert.equal(existsSync(new URL(`../server/routes/_api/${name}.post.ts`, import.meta.url)), false, name)
  assert.doesNotMatch(caddy, /php_form_post|path \/gio-hang/)
})

test('checkout problems come back once to the cart page without racing the header', () => {
  assert.match(shop, /session->pull\('checkout_notice'\)/)
  assert.match(cartPage, /onMounted\(async \(\) => \{[\s\S]*?_api\/checkout-notice/)
  assert.match(cartPage, /role="alert"/)
  assert.doesNotMatch(siteHeader, /checkout-notice/)
})
