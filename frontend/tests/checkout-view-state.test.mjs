import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { checkoutViewState } from '../shared/checkout-state.ts'

const ok = { count: 2, canCheckout: true }
const meta = { cartReady: true }

test('checkout view state separates empty, quote, blocked, error and ready', () => {
  assert.equal(checkoutViewState({ loadError: true, cart: ok, checkout: meta }), 'error')
  assert.equal(checkoutViewState({ loadError: false, cart: null, checkout: meta }), 'error')
  assert.equal(checkoutViewState({ loadError: false, cart: ok, checkout: null }), 'error')
  assert.equal(checkoutViewState({ loadError: false, cart: { count: 0, canCheckout: false }, checkout: { cartReady: false } }), 'empty')
  assert.equal(checkoutViewState({ loadError: false, cart: { count: 1, canCheckout: false }, checkout: { cartReady: false } }), 'quote')
  assert.equal(checkoutViewState({ loadError: false, cart: ok, checkout: { cartReady: false } }), 'blocked')
  assert.equal(checkoutViewState({ loadError: false, cart: ok, checkout: meta }), 'ready')
})

test('checkout page renders quote copy only in the quote branch', () => {
  const page = readFileSync(new URL('../app/pages/thanh-toan.vue', import.meta.url), 'utf8')
  assert.match(page, /viewState === 'empty'[\s\S]*Giỏ hàng đang trống/)
  assert.doesNotMatch(page, /Giỏ đang trống hoặc có sản phẩm/)
  assert.match(page, /<ul v-if="viewState === 'quote'" class="checkout-assure">/)
  assert.match(page, /:current="viewState === 'empty' \? 'cart' : 'checkout'"/)
})
