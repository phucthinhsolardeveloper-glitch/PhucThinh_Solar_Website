import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const component = readFileSync(new URL('../app/components/contact/ContactLocations.vue', import.meta.url), 'utf8')
const contractTests = readFileSync(new URL('./contact-contract.test.mjs', import.meta.url), 'utf8')

test('contact map embeds the validated CMS URL and does not send plain addresses as search queries', () => {
  assert.match(component, /<iframe\s+v-if="selected\?\.mapUrl"[\s\S]*:src="selected\.mapUrl"/)
  assert.doesNotMatch(component, /mapEmbedUrl|maps\?q=|encodeURIComponent\(location\.address\)/)
  assert.match(component, /referrerpolicy="no-referrer-when-downgrade"/)
  assert.match(component, /v-if="selected\.mapUrl"[\s\S]*target="_blank"[\s\S]*rel="noopener noreferrer"/)
})

test('contact map URLs continue to be validated against the Google Maps allowlist', () => {
  assert.match(contractTests, /rejects unsafe media and map URLs/)
  assert.match(contractTests, /https:\/\/evil\.example\/maps/)
})

test('contact branches and map use equal desktop column widths', () => {
  assert.match(component, /items-stretch gap-8 lg:grid-cols-\[minmax\(0,1fr\)_minmax\(0,1fr\)\] lg:gap-10/)
  assert.match(component, /lg:auto-rows-fr lg:content-stretch/)
  assert.match(component, /lg:h-full lg:items-center/)
  assert.doesNotMatch(component, /lg:grid-cols-\[minmax\(0,\.8fr\)_minmax\(0,1\.2fr\)\]/)
})
