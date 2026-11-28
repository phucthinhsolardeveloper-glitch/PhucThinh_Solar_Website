import assert from 'node:assert/strict'
import { existsSync, readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(`../${path}`, import.meta.url), 'utf8')

test('every bank the Admin list offers has a logo the transfer card can show', () => {
  const badges = [...read('../backend/app/Domain/Commerce/VietQr.php').matchAll(/'9704\d{2}' => \['[^']+', '([A-Z]+)'/g)].map((m) => m[1].toLowerCase())
  assert.ok(badges.length >= 30)
  const page = read('app/components/order/OrderTransferCard.vue')
  for (const code of badges) {
    assert.ok(existsSync(new URL(`../public/assets/images/payments/banks/${code}.png`, import.meta.url)), `${code}.png`)
    assert.ok(page.includes(`'${code}'`), `${code} listed`)
  }
  for (const code of page.match(/BANK_MARKS = new Set\(\[([^\]]*)\]/)[1].match(/[a-z]+/g)) {
    assert.ok(existsSync(new URL(`../public/assets/images/payments/banks/${code}-mark.png`, import.meta.url)), `${code}-mark.png`)
  }
})
