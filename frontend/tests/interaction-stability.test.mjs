import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('news search submits through Nuxt navigation and resets pagination', () => {
  for (const path of ['../app/components/news/NewsCategoryPage.vue', '../app/pages/migration-news-preview.vue']) {
    const source = read(path)
    assert.match(source, /@submit\.prevent="submitSearch"/)
    assert.match(source, /navigateTo\(\{ path: (?:route\.path|publicPath\.value), query \}\)/)
    assert.match(source, /delete query\.p/)
    assert.doesNotMatch(source, /<form[^>]*method="get"/)
  }
})

test('brand sort and filters keep the current result layout while refreshing', () => {
  const source = read('../app/components/catalog/BrandDetailPage.vue')
  assert.match(source, /`migration-brand-detail-\$\{props\.slug\}`/)
  assert.match(source, /\{ watch: \[query\] \}/)
  assert.match(source, /v-if="pending && !data"/)
  assert.match(source, /aria-label="Sản phẩm thương hiệu" :aria-busy="pending"/)
})

test('product, gallery and video refreshes retain their previous content during route query changes', () => {
  const productData = read('../app/composables/useProductPreviewData.ts')
  const productPage = read('../app/pages/san-pham/[slug].vue')
  const gallery = read('../app/pages/migration-gallery-preview.vue')
  const video = read('../app/pages/migration-video-preview.vue')

  assert.match(productData, /lastResolvedPayload/)
  assert.match(productData, /return productPending\.value \? lastResolvedPayload\.value : null/)
  assert.match(productPage, /v-if="pending && !product"/)
  assert.match(gallery, /v-if="pending && !gallery"/)
  assert.match(video, /v-if="pending && !payload"/)
})

test('all overlays share a reference-counted scroll lock with scrollbar compensation', () => {
  const lock = read('../app/composables/useDocumentScrollLock.ts')
  assert.match(lock, /owners\.value\.length === 0/)
  assert.match(lock, /window\.innerWidth - document\.documentElement\.clientWidth/)
  assert.match(lock, /originalStyle\.value\?\.overflow/)
  assert.match(lock, /originalStyle\.value\?\.paddingRight/)
  for (const path of [
    '../app/components/SiteHeader.vue',
    '../app/components/product-preview/ProductPreviewGallery.vue',
    '../app/components/project-detail/ProjectDetailGallery.vue',
    '../app/components/solution-detail/SolutionDetailGallery.vue',
    '../app/pages/migration-gallery-detail-preview.vue',
    '../app/pages/migration-video-preview.vue',
  ]) assert.match(read(path), /useDocumentScrollLock\(\)/)
})
