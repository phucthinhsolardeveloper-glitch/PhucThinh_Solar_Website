import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const page = readFileSync(new URL('../app/components/product-detail/ProductDetailPage.vue', import.meta.url), 'utf8')
const publicPage = readFileSync(new URL('../app/pages/san-pham/[slug].vue', import.meta.url), 'utf8')
const applications = readFileSync(new URL('../app/components/product-detail/ProductDetailApplications.vue', import.meta.url), 'utf8')
const guides = readFileSync(new URL('../app/components/product-detail/ProductDetailBuyingGuide.vue', import.meta.url), 'utf8')

test('PDP exposes CMS applications and buying guide sections with section navigation', () => {
  assert.match(page, /id: 'ung-dung-san-pham', label: 'Ứng dụng'/)
  assert.match(page, /id: 'huong-dan-lua-chon', label: 'Lựa chọn'/)
  assert.match(page, /<ProductDetailApplications v-if="applications\?\.items\.length"/)
  assert.match(page, /<ProductDetailBuyingGuide v-if="buyingGuides\?\.length"/)
  for (const routePage of [publicPage]) {
    assert.match(routePage, /:applications="payload!\.applications"/)
    assert.match(routePage, /:buying-guides="payload!\.buyingGuides"/)
  }
})

test('PDP application media only renders when a CMS image is present', () => {
  assert.match(applications, /v-if="item\.image"/)
  assert.match(applications, /aspect-\[8\/5\]/)
  assert.match(applications, /aria-labelledby="product-applications-title"/)
  assert.match(guides, /aria-labelledby="product-buying-guide-title"/)
})
