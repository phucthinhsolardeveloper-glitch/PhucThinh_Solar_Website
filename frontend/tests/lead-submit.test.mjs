import assert from 'node:assert/strict'
import { readFileSync, readdirSync } from 'node:fs'
import { test } from 'node:test'
import { LEAD_FALLBACK_MESSAGE, LeadFormError, leadErrorMessage, sendWithFreshToken, toLeadFormError } from '../shared/lead-submit.ts'
import { purchaseAmounts } from '../shared/purchase-amounts.ts'
import { normalizeVnPhone } from '../shared/vn-phone.ts'

const read = (path) => readFileSync(new URL(`../${path}`, import.meta.url), 'utf8')
const refused = (statusCode, message) => Object.assign(new Error(`[POST] ${statusCode}`), { statusCode, data: message ? { error: { message } } : undefined })

test('typed phone numbers read as 10 digits, anything else is refused', () => {
  for (const typed of ['0944555957', '0944 555 957', '0944.555.957', '0944-555-957', '+84944555957', '+84 944 555 957', '84944555957']) {
    assert.equal(normalizeVnPhone(typed), '0944555957', typed)
  }
  for (const typed of ['', '123', '094455595', '09445559578', '0244555957', '0944abc957', '(0944)555957', '+1 944 555 957', '+840944555957', '0944555957-']) {
    assert.equal(normalizeVnPhone(typed), null, typed)
  }
})

test("Laravel's own message is shown; 419, 429 and unknown failures never show technical text", () => {
  for (const status of [422, 429, 419]) assert.equal(leadErrorMessage(toLeadFormError(refused(status, `Lý do ${status}.`))), `Lý do ${status}.`)
  assert.match(leadErrorMessage(toLeadFormError(refused(429))), /quá nhanh/)
  assert.match(leadErrorMessage(toLeadFormError(refused(419))), /hết hạn/)
  assert.equal(leadErrorMessage(toLeadFormError(refused(500))), LEAD_FALLBACK_MESSAGE)
  assert.equal(leadErrorMessage(new TypeError('fetch failed')), LEAD_FALLBACK_MESSAGE)
  assert.ok(toLeadFormError(refused(422, 'x')) instanceof LeadFormError)
})

test('a refused form token is refreshed and the request sent exactly once more', async () => {
  const run = async (outcomes) => {
    let sends = 0
    let refreshes = 0
    const result = await sendWithFreshToken(async () => {
      const outcome = outcomes[sends++]
      if (outcome !== 'ok') throw refused(outcome)
      return outcome
    }, async () => { refreshes++ }).catch((reason) => reason.statusCode)
    return { result, sends, refreshes }
  }
  assert.deepEqual(await run(['ok']), { result: 'ok', sends: 1, refreshes: 0 })
  assert.deepEqual(await run([419, 'ok']), { result: 'ok', sends: 2, refreshes: 1 })
  assert.deepEqual(await run([419, 419, 'ok']), { result: 419, sends: 2, refreshes: 1 })
  for (const status of [422, 429, 500]) assert.deepEqual(await run([status, 'ok']), { result: status, sends: 1, refreshes: 0 })
})

test('every lead form shows the shared error message and names itself for analytics', () => {
  const components = new URL('../app/components/', import.meta.url)
  const forms = readdirSync(components, { recursive: true }).filter((name) => name.endsWith('.vue'))
    .map((name) => [name, read(`app/components/${name}`)]).filter(([, source]) => source.includes('useLeadForm()'))
  assert.equal(forms.length, 7, forms.map(([name]) => name).join(', '))
  for (const [name, source] of forms) {
    assert.match(source, /leadErrorMessage\(reason\)/, name)
    assert.doesNotMatch(source, /reason\??\.data\??\.statusMessage/, name)
    assert.match(source, /await submit\('(contact|dealer|product)'/, name)
    assert.match(source, /(\}|summary \}), ('(home|contact_page|solar_estimator|product_page|dealer_page|dealer_popup)'|quoteCartMode\.value \? 'cart_quote' : 'consultation_popup')\)/, name)
  }
})

test('generate_lead carries only fixed values, and the estimator reports once per visit without the bill', () => {
  assert.match(read('app/composables/useLeadForm.ts'), /pushDataLayer\('generate_lead', \{ form_kind: kind, form_source: formSource, page_path: /)
  const estimator = read('app/components/estimator/SolarEstimatorPage.vue')
  assert.match(estimator, /if \(!visitorAsked \|\| resultReported\) return/)
  assert.match(estimator, /pushDataLayer\('estimator_result', \{ building_type: form\.type, with_battery: [^}]*page_path: window\.location\.pathname \}\)/)
})

test('GA4 purchase value is the goods; delivery is reported on its own', () => {
  assert.deepEqual(purchaseAmounts({ totalPrice: 1030000, subtotal: 1000000, shippingFee: 30000 }), { value: 1000000, shipping: 30000 })
  assert.deepEqual(purchaseAmounts({ totalPrice: 1000000, subtotal: 1000000, shippingFee: 0 }), { value: 1000000, shipping: 0 })
  assert.deepEqual(purchaseAmounts({ totalPrice: 1030000 }), { value: 1030000 })
  assert.match(read('app/components/order/OrderThanksPage.vue'), /\.\.\.purchaseAmounts\(current\.order\)/)
  assert.match(read('app/pages/thanh-toan.vue'), /phone: normalizeVnPhone\(form\.phone\) !== null/)
})
