import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { editorialListPage } from '../shared/editorial-public-route.ts'

const read = (path) => readFileSync(new URL(`../${path}`, import.meta.url), 'utf8')

test('page 2 of an article list is its own page; searches, junk and pages past the end are filtered views', () => {
  const pagination = { total: 29, perPage: 6 }
  assert.deepEqual(editorialListPage({}, pagination), { page: 1, filtered: false })
  assert.deepEqual(editorialListPage({ p: '1' }, pagination), { page: 1, filtered: false })
  assert.deepEqual(editorialListPage({ p: '5' }, pagination), { page: 5, filtered: false })
  for (const query of [{ p: '6' }, { p: '0' }, { p: '02' }, { p: 'abc' }, { p: ['2', '3'] }]) assert.deepEqual(editorialListPage(query, pagination), { page: 1, filtered: true }, JSON.stringify(query))
  assert.deepEqual(editorialListPage({ q: 'đèn' }, pagination), { page: 1, filtered: true })
  assert.deepEqual(editorialListPage({ p: '2', q: 'đèn' }, pagination), { page: 2, filtered: true })
  assert.equal(editorialListPage({ p: '2' }, null).filtered, true)
})

test('article lists give their own canonical to each real page', () => {
  for (const file of ['app/components/views/NewsView.vue', 'app/components/news/NewsCategoryPage.vue']) {
    const source = read(file)
    assert.match(source, /canonicalPath: listCanonicalPath,/, file)
    assert.match(source, /\?p=\$\{listPage\.value\.page\}/, file)
  }
})

test('empty photo and video libraries are not offered to search engines', () => {
  assert.match(read('app/components/views/GalleryView.vue'), /gallery\.value\.albums\.length > 0/)
  assert.match(read('app/components/views/VideoView.vue'), /payload\.value\.videos\.length > 0/)
})

test('project category and brand links point at the address search engines index', () => {
  assert.match(read('app/components/projects/ProjectFilters.vue'), /category\.canonicalPath \|\| category\.url/)
  assert.match(read('app/components/views/ProjectDetailView.vue'), /links\.generic\(`\/\$\{payload\.project\.category\.slug\}`\)/)
  assert.match(read('app/components/product-detail/ProductDetailPage.vue'), /:to="product\.brandUrl"/)
  assert.match(read('app/components/about/AboutPage.vue'), /brand\.brandPath \|\| brand\.href/)
})

test('a rating is marked up only when approved reviews are shown, and a quote-only product has no offer', () => {
  const detail = read('app/composables/useProductDetailData.ts')
  assert.match(detail, /if \(extras\.value && extras\.value\.reviews\.total > 0\) \{\s*productNode\.aggregateRating/)
  assert.match(detail, /const offer = productOfferNode\(pricing, p\.merchant, canonicalUrl, siteOrigin\)\s*if \(offer\) productNode\.offers = offer/)
})
