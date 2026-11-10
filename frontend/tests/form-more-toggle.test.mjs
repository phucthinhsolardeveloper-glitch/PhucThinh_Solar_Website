import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(`../${path}`, import.meta.url), 'utf8')

// A "tb:hidden" utility on the toggle never hid it on PC (its scoped display:flex outranks the utility), so the
// forms whose details are always open on wide screens ask for phoneOnly instead.
test('the "Thêm chi tiết" button is phone-only where wide screens already show the fields', () => {
  assert.match(read('app/components/FormMoreToggle.vue'), /@media \(min-width: 821px\) \{ \.form-more--phone \{ display: none; \} \}/)
  for (const file of ['app/components/contact/ContactLeadForm.vue', 'app/components/home/HomeContact.vue', 'app/components/product-detail/ProductConsultationForm.vue']) {
    const source = read(file)
    assert.match(source, /<FormMoreToggle[^>]*phone-only/, file)
    assert.doesNotMatch(source, /<FormMoreToggle[^>]*tb:hidden/, file)
  }
})
