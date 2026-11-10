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

// Wide screens: search replaces the header (covers it, field centred at header height) and fades out on close;
// no full-page backdrop blur, which stalled the fade on ordinary laptops.
test('desktop search covers the header and closes with a fade, without a page-wide blur', () => {
  const header = read('app/components/SiteHeader.vue')
  assert.match(header, /<Transition name="pts-search"/)
  assert.match(header, /\.pts-search-leave-to \.pts-search-panel \{ opacity: 0;/)
  assert.match(header, /@media \(min-width: 821px\) \{\n[^]*?\.pts-search-panel \{ top: 0 !important;/)
  assert.match(header, /grid-template-rows: minmax\(calc\(var\(--pts-header-h, 89px\) - 14px\), auto\) auto/)
  assert.match(header, /\.pts-search-panel \{ top: 0 !important; border-top: 0 !important; padding-block: 14px 24px !important;/)
  assert.doesNotMatch(header.match(/\.pts-search-scrim \{ top: 0[^}]*\}/)[0], /backdrop-filter/)
})

// Phones showed the chosen category chip orange: the .m-lg glass (saturate 1.8) sat over the gold pill.
test('nothing on a gliding pill applies a backdrop filter that would re-colour the gold', () => {
  const css = read('app/assets/css/main.css')
  assert.match(css, /\[data-pill-rail\] \[data-pill-on\] \{ -webkit-backdrop-filter: none !important; backdrop-filter: none !important; \}/)
  assert.match(css, /\[data-pill-rail\] \.catalog-category-chip \{[^}]*backdrop-filter: none !important;/)
})
