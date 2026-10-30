import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { normalizeCommerceQuantity } from '../shared/commerce-quantity.ts'

const frontendRoot = new URL('../', import.meta.url)
const read = (path) => readFileSync(new URL(path, frontendRoot), 'utf8')

test('catalog, brand, and search route ownership is permanent and preserves query state', () => {
  const middleware = [
    read('app/middleware/catalog-public-route.ts'),
    read('app/middleware/brand-detail-public-route.ts'),
    read('app/middleware/search-public-route.ts'),
  ].join('\n')
  assert.doesNotMatch(middleware, /PublicRouteEnabled|phpPublicRouteUrl|redirectCode: 307/)
  assert.match(middleware, /path: targetPath, query: to\.query/)
  assert.match(middleware, /path: SEARCH_PUBLIC_PATH, query: to\.query/)
})

test('commerce quantity normalization enforces integer range 1–999', () => {
  assert.equal(normalizeCommerceQuantity(1), 1)
  assert.equal(normalizeCommerceQuantity(0), 1)
  assert.equal(normalizeCommerceQuantity(-10), 1)
  assert.equal(normalizeCommerceQuantity(3.8), 3)
  assert.equal(normalizeCommerceQuantity(1000), 999)
  assert.equal(normalizeCommerceQuantity(Number.NaN), 1)
})

test('PDP and cart use the shared accessible quantity stepper and commit cart changes only after input commits', () => {
  const stepper = read('app/components/commerce/QuantityStepper.vue')
  const purchase = read('app/components/product-preview/ProductPurchasePanel.vue')
  const cart = read('app/components/cart/CartPage.vue')
  assert.match(stepper, /h-11 w-11/)
  assert.match(stepper, /min="1"[\s\S]*max="999"/)
  assert.match(stepper, /change: \[value: number\]/)
  assert.match(purchase, /normalizeCommerceQuantity\(quantity\.value\)/)
  assert.match(purchase, /<QuantityStepper v-if="pricing\.mode === 'price'" v-model="quantity"[^>]*inline-label/)
  assert.match(cart, /<QuantityStepper/)
  assert.match(cart, /@change="updateItem/)
})

test('cart puts line total before quantity in explicit desktop columns', () => {
  const cart = read('app/components/cart/CartPage.vue')
  assert.match(cart, /tb:grid-cols-\[112px_minmax\(0,1fr\)_150px_156px\]/)
  assert.match(cart, /tb:col-start-3/)
  assert.match(cart, /tb:col-start-4/)
})
