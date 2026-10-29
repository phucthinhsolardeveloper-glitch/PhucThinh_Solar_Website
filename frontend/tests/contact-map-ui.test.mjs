import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const component = readFileSync(new URL('../app/components/contact/ContactLocations.vue', import.meta.url), 'utf8')
const contractTests = readFileSync(new URL('./contact-contract.test.mjs', import.meta.url), 'utf8')

test('contact map embeds the validated CMS URL and does not send plain addresses as search queries', () => {
  assert.match(component, /v-if="selected\?\.mapUrl"[\s\S]*<iframe[\s\S]*:src="selected\.mapUrl"/)
  assert.doesNotMatch(component, /mapEmbedUrl|maps\?q=|encodeURIComponent\(location\.address\)/)
  assert.match(component, /referrerpolicy="no-referrer-when-downgrade"/)
  assert.match(component, /v-if="location\.mapUrl"[\s\S]*target="_blank"[\s\S]*rel="noopener noreferrer"/)
})

test('contact map URLs continue to be validated against the Google Maps allowlist', () => {
  assert.match(contractTests, /rejects unsafe media and map URLs/)
  assert.match(contractTests, /https:\/\/evil\.example\/maps/)
})
