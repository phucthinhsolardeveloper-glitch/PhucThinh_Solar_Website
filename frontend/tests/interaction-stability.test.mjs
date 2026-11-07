import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('news search submits through Nuxt navigation and resets pagination', () => {
  // Owner decision 2026-11-04: no in-page article search (the header search covers it);
  // the category page keeps submitSearch for its "clear search" reset.
  for (const path of ['../app/components/news/NewsCategoryPage.vue']) {
    const source = read(path)
    assert.match(source, /navigateTo\(\{ path: (?:route\.path|publicPath\.value), query \}\)/)
    assert.match(source, /delete query\.p/)
    assert.doesNotMatch(source, /<form[^>]*method="get"/)
  }
  assert.doesNotMatch(read('../app/components/news/NewsMasthead.vue'), /<form/)
})

test('brand sort and filters keep the current result layout while refreshing', () => {
  const source = read('../app/components/catalog/BrandDetailPage.vue')
  assert.match(source, /`brand-detail-\$\{props\.slug\}`/)
  assert.match(source, /\{ watch: \[query\] \}/)
  assert.match(source, /v-if="pending && !data"/)
  assert.match(source, /aria-label="Sản phẩm thương hiệu" :aria-busy="pending"/)
})

test('product, gallery and video refreshes retain their previous content during route query changes', () => {
  const productData = read('../app/composables/useProductDetailData.ts')
  const productPage = read('../app/pages/san-pham/[slug].vue')
  const gallery = read('../app/components/views/GalleryView.vue')
  const video = read('../app/components/views/VideoView.vue')

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
    '../app/components/product-detail/ProductDetailGallery.vue',
    '../app/components/project-detail/ProjectDetailGallery.vue',
    '../app/components/solution-detail/SolutionDetailGallery.vue',
    '../app/components/views/GalleryDetailView.vue',
    '../app/components/views/VideoView.vue',
  ]) assert.match(read(path), /useDocumentScrollLock\(\)/)
})
