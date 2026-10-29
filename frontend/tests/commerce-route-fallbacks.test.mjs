import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { legacyCatalogFilterQuery } from '../shared/public-route-gate.ts'
import { legacySearchFilterQuery } from '../shared/public-route-query.ts'
import { normalizeCommerceQuantity } from '../shared/commerce-quantity.ts'

const frontendRoot = new URL('../', import.meta.url)
const read = (path) => readFileSync(new URL(path, frontendRoot), 'utf8')

test('flag-off catalog fallback maps only validated filters to the legacy query names', () => {
  assert.deepEqual(legacyCatalogFilterQuery({ page: '4', brand: '2,5', price: '1,3', sort: 'abc-desc' }), {
    p: '4', is_brand: '2,5', is_price: '1,3', is_orderby: 'abc-desc',
  })
  assert.deepEqual(legacyCatalogFilterQuery({ page: '0', brand: '2,', price: '9', sort: 'anything' }), {})
  assert.match(read('app/middleware/catalog-public-route.ts'), /legacyCatalogFilterQuery\(to\.query\)/)
})

test('flag-off brand fallback forwards only supported page, price and sort state', () => {
  const middleware = read('app/middleware/brand-detail-public-route.ts')
  assert.match(middleware, /page:\s*to\.query\.page/)
  assert.match(middleware, /price:\s*to\.query\.price/)
  assert.match(middleware, /sort:\s*to\.query\.sort/)
  assert.match(middleware, /legacyCatalogFilterQuery\(/)
})

test('flag-off search fallback preserves safe keyword/page and drops unsafe values', () => {
  assert.deepEqual(legacySearchFilterQuery({ keyword: 'đèn sân vườn', page: '3', sort: 'ignored' }), {
    keyword: 'đèn sân vườn', p: '3',
  })
  assert.deepEqual(legacySearchFilterQuery({ keyword: 'x'.repeat(101), page: '-2' }), {})
  assert.deepEqual(legacySearchFilterQuery({ keyword: 'unsafe\nquery', page: ['2'] }), {})
  assert.match(read('app/middleware/search-public-route.ts'), /legacySearchFilterQuery\(to\.query\)/)
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
