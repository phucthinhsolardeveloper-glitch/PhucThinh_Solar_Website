import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { leadCampaignFromUrl, leadSourceFromUrl } from '../shared/lead-source.ts'

test('keeps the page and safe campaign codes while dropping personal query data', () => {
  assert.equal(leadSourceFromUrl('https://phucthinhsolar.com/san-pham/den-nlmt/?utm_source=google&utm_campaign=november-2026&email=a%40b.vn#form'),
    '/san-pham/den-nlmt/?utm_source=google&utm_campaign=november-2026')
  assert.equal(leadSourceFromUrl('https://phucthinhsolar.com/tin-tuc/?utm_source=a%40b.vn&phone=0912345678'), '/tin-tuc/')
  assert.equal(leadCampaignFromUrl('https://phucthinhsolar.com/?utm_source=google&utm_medium=cpc'), 'utm_source=google&utm_medium=cpc')
  assert.equal(leadSourceFromUrl('https://phucthinhsolar.com/san-pham/den-nlmt/', 'utm_source=google&utm_campaign=solar-2026&email=x%40y.vn'),
    '/san-pham/den-nlmt/?utm_source=google&utm_campaign=solar-2026')
})

test('does not attach private or invalid routes to a lead', () => {
  assert.equal(leadSourceFromUrl('https://phucthinhsolar.com/admin/users'), '')
  assert.equal(leadSourceFromUrl('javascript:alert(1)'), '')
  assert.equal(leadSourceFromUrl('https://phucthinhsolar.com/san-pham/%2E%2E/'), '')
})

test('keeps Google and Facebook ad click ids (Google first), within 255 characters', () => {
  const gclid = 'Cj0KCQiA' + 'x'.repeat(80)
  assert.equal(leadSourceFromUrl(`https://phucthinhsolar.com/lien-he/?fbclid=FB_1&utm_source=google&gclid=${gclid}`),
    `/lien-he/?gclid=${gclid}&utm_source=google&fbclid=FB_1`)
  const long = 'y'.repeat(190)
  const source = leadSourceFromUrl(`https://phucthinhsolar.com/lien-he/?gclid=${long}&fbclid=${long}`)
  assert.equal(source, `/lien-he/?gclid=${long}`)
  assert.ok(source.length <= 255)
  assert.equal(leadSourceFromUrl('https://phucthinhsolar.com/?gclid=bad%20value'), '/')
})

test('ad clicks are remembered 30 days on the device and analytics never carries what the visitor typed', () => {
  const read = (path) => readFileSync(new URL(`../${path}`, import.meta.url), 'utf8')
  assert.match(read('app/plugins/lead-attribution.client.ts'), /localStorage\.setItem\(LEAD_CAMPAIGN_KEY/)
  assert.match(read('app/utils/leadCampaign.ts'), /LEAD_CAMPAIGN_DAYS \* 86_400_000/)
  const lead = read('app/composables/useLeadForm.ts')
  assert.match(lead, /pushDataLayer\('generate_lead', \{ form_kind: kind, page_path:/)
  assert.doesNotMatch(read('app/composables/useDataLayer.ts').replace(/^\s*\/\/.*$/gm, ''), /phone|email|fullname/i)
  assert.match(read('app/plugins/contact-clicks.client.ts'), /click_call/)
  assert.match(read('app/plugins/contact-clicks.client.ts'), /click_zalo/)
  for (const [file, event] of [['app/pages/san-pham/[slug].vue', 'view_item'], ['app/composables/useShopCart.ts', 'add_to_cart'], ['app/pages/thanh-toan.vue', 'begin_checkout'], ['app/components/order/OrderThanksPage.vue', "pushDataLayer('purchase'"]]) {
    assert.ok(read(file).includes(event), `${file} sends ${event}`)
  }
})
