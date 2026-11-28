import test from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const read = (path) => readFileSync(new URL(`../${path}`, import.meta.url), 'utf8')

test('order lookup posts code and phone straight to the API with the form token, and keeps nothing', () => {
  const page = read('app/components/order/OrderLookupPage.vue')
  assert.match(page, /\$fetch<unknown>\('\/api\/v1\/order-lookup'/)
  assert.match(page, /csrfToken: meta\.csrfToken, code: code\.value, phone: phone\.value/)
  assert.doesNotMatch(page, /localStorage|sessionStorage|pushDataLayer|gtag/)
  assert.match(page, /route\.query\.ma/)
})

test('the thank-you page and the lookup share one transfer card', () => {
  for (const file of ['OrderThanksPage.vue', 'OrderLookupPage.vue']) assert.match(read(`app/components/order/${file}`), /<OrderTransferCard /)
  assert.match(read('app/components/order/OrderTransferCard.vue'), /<slot \/>/)
})

test('the lookup path is reserved so no product or post can take it', () => {
  assert.match(read('shared/public-route-gate.ts'), /'tra-cuu-don-hang'/)
})
