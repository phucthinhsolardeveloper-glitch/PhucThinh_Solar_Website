import { test } from 'node:test'
import assert from 'node:assert/strict'
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
