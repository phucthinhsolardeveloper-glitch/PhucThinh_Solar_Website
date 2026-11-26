import assert from 'node:assert/strict'
import { readFileSync, readdirSync } from 'node:fs'
import { test } from 'node:test'
import { leadErrorType, leadFormRefused, leadFormStart } from '../shared/lead-analytics.ts'

const read = (path) => readFileSync(new URL(`../${path}`, import.meta.url), 'utf8')
const form = (source, kind) => ({ getAttribute: (name) => ({ 'data-lead-form': source, 'data-lead-kind': kind })[name] ?? null })
const field = (parent) => ({ closest: (selector) => (selector === 'form[data-lead-form]' ? parent : null) })

test('a lead form starts once, and only a form that names itself with fixed values', () => {
  const started = new WeakSet()
  const contact = form('contact_page', 'contact')
  assert.deepEqual(leadFormStart(field(contact), started), { form_kind: 'contact', form_source: 'contact_page' })
  assert.equal(leadFormStart(field(contact), started), null)
  // The same form opened again is a new element.
  assert.deepEqual(leadFormStart(field(form('contact_page', 'contact')), started), { form_kind: 'contact', form_source: 'contact_page' })
  for (const other of [field(null), null, {}, field(form('0912345678', 'contact')), field(form('home', 'Nguyễn Văn A'))]) {
    assert.equal(leadFormStart(other, started), null)
  }
})

test('a failed submit is described by its status alone', () => {
  assert.deepEqual([null, 422, 419, 429, 500, 503, 403].map(leadErrorType), ['network', 'validation', 'session', 'rate_limit', 'server', 'server', 'other'])
})

test('every lead form is marked with the source it submits as', () => {
  const components = new URL('../app/components/', import.meta.url)
  const forms = readdirSync(components, { recursive: true }).filter((name) => name.endsWith('.vue'))
    .map((name) => [name, read(`app/components/${name}`)]).filter(([, source]) => source.includes('useLeadForm()'))
  for (const [name, source] of forms) {
    const marked = source.match(/<form [^>]*?:?data-lead-form="([^"]+)"[^>]*data-lead-kind="(contact|dealer|product)"/)
    assert.ok(marked, `${name} marks its lead form`)
    const sent = source.match(/await submit\('(contact|dealer|product)'/)
    assert.equal(marked[2], sent[1], `${name} kind`)
    assert.match(source, new RegExp(marked[1].includes('?') ? "quoteCartMode\\.value \\? 'cart_quote' : 'consultation_popup'" : `'${marked[1]}'\\)`), `${name} source`)
  }
})

test('the funnel events carry the form and the page only', () => {
  const lead = read('app/composables/useLeadForm.ts')
  assert.match(lead, /const funnel = \{ form_kind: kind, form_source: formSource, page_path: page \}/)
  // One for a send that reaches the server, one for a send the form itself refuses.
  assert.equal(lead.match(/pushDataLayer\('lead_form_submit'/g).length, 2)
  assert.equal(lead.match(/pushDataLayer\('generate_lead'/g).length, 1)
  const plugin = read('app/plugins/lead-form-start.client.ts')
  assert.match(plugin, /if \(!event\.isTrusted\) return/)
  assert.match(plugin, /addEventListener\('click'/)
  assert.match(plugin, /addEventListener\('invalid'/)
  assert.doesNotMatch(plugin.replace(/^\s*\/\/.*$/gm, ''), /\.value|textContent|innerText/)
})

test('a send refused by the browser is reported once per attempt, only right after the visitor tried to send', () => {
  const attempts = new WeakMap()
  const contact = form('contact_page', 'contact')
  assert.equal(leadFormRefused(field(contact), attempts, 1000), null, 'no send was tried')
  attempts.set(contact, 1000)
  assert.deepEqual(leadFormRefused(field(contact), attempts, 1100), { form_kind: 'contact', form_source: 'contact_page' })
  assert.equal(leadFormRefused(field(contact), attempts, 1101), null, 'a second wrong field in the same attempt')
  attempts.set(contact, 1000)
  assert.equal(leadFormRefused(field(contact), attempts, 4000), null, 'too long after the send')
  attempts.set(contact, 1000)
  assert.equal(leadFormRefused(field(form('0912345678', 'contact')), attempts, 1100), null)
})

test('forms that check their own fields report a refused send', () => {
  assert.match(read('app/components/estimator/SolarEstimatorPage.vue'), /refused\('contact', 'solar_estimator'\)/)
  assert.match(read('app/components/product-detail/ProductConsultationForm.vue'), /refused\('product', 'product_page'\)/)
  assert.match(read('app/components/modal/StorefrontDealerModal.vue'), /refused\('dealer', 'dealer_popup'\)/)
})
