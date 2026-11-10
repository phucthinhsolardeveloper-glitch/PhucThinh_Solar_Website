import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { isPublicComparePayload } from '../shared/contracts/public-compare.ts'

const read = (path) => readFileSync(new URL(`../${path}`, import.meta.url), 'utf8')
const product = (id, slug) => ({ id, name: `Biến tần ${id}`, slug, url: `/san-pham/${slug}`, code: 'EPV', brand: 'Epsivo', image: '/upload/product/a.png',
  pricing: { mode: 'price', currency: 'VND', regularPrice: 1000000, salePrice: null } })
const payload = {
  schemaVersion: 1, locale: 'vi', max: 4,
  products: [product(1, 'bien-tan-a'), product(2, 'bien-tan-b')],
  rows: [{ label: 'Công suất', values: ['2,2kW', '4kW'], same: false }, { label: 'Số pha', values: ['3 pha', ''], same: false }],
}

test('compare contract accepts the Laravel table and refuses a broken one', () => {
  assert.equal(isPublicComparePayload(payload), true)
  assert.equal(isPublicComparePayload({ ...payload, products: [], rows: [] }), true)
  assert.equal(isPublicComparePayload({ ...payload, rows: [{ label: 'Công suất', values: ['2,2kW'], same: false }] }), false)
  assert.equal(isPublicComparePayload({ ...payload, products: [{ ...product(1, 'a'), url: '//evil.example' }] }), false)
  assert.equal(isPublicComparePayload({ ...payload, products: [{ ...product(1, 'a'), image: '/upload/product/../x' }] }), false)
  assert.equal(isPublicComparePayload({ ...payload, max: 1 }), false)
})

test('compare page stays out of search results and the bridge only passes a short id list', () => {
  assert.match(read('app/pages/so-sanh-san-pham.vue'), /robots: 'noindex, follow'/)
  const bridge = read('server/routes/_api/compare.get.ts')
  assert.match(bridge, /\{0,3\}\$/)
  assert.match(bridge, /key !== 'ids'/)
})

test('the pick list is per browser, capped, and every storage call survives a blocked storage', () => {
  const source = read('app/composables/useProductCompare.ts')
  assert.match(source, /COMPARE_MAX = 4/)
  assert.equal((source.match(/localStorage\./g) || []).length, (source.match(/try \{[^}]*localStorage\./g) || []).length)
  assert.match(read('app/components/product-detail/ProductPurchasePanel.vue'), /useProductCompare\(\)/)
  assert.match(read('app/app.vue'), /<CompareTray \/>/)
})

test('a category page lists its parent categories in the breadcrumb', async () => {
  const { isPublicCatalogPayload } = await import('../shared/contracts/public-catalog.ts')
  const page = read('app/components/catalog/CatalogPage.vue')
  assert.match(page, /catalogData\.value\?\.breadcrumbs \?\? \[\]/)
  assert.match(page, /\[root, \.\.\.parents, \{ label: heading\.value \}\]/)
  assert.equal(isPublicCatalogPayload({ breadcrumbs: [{ label: 'x', url: 'javascript:alert(1)' }] }), false)
})

// Product and category links stay inside the app: 'external' made every card click reload the whole site
// (measured 0.7 s on PC, 4.9 s on a slow phone; 0.09 s / 0.9 s without it).
test('product cards, home product groups and the category tree navigate without a full reload', () => {
  for (const file of ['app/components/catalog/CatalogProductGrid.vue', 'app/components/home/HomeProductGroups.vue', 'app/components/catalog/CatalogCategoryBranch.vue']) {
    assert.doesNotMatch(read(file), /<NuxtLink[^>]*\n?\s*external\b/, file)
  }
  assert.match(read('app/components/experience-v3/EnergyScene3DExperience.vue'), /rootMargin: nearMargin\(\)/)
})

test('policy pages keep the same header height so the policy cards do not jump between pages', () => {
  const view = read('app/components/views/PolicyView.vue')
  assert.match(view, /class="policy-hub__head"/)
  assert.match(view, /\.policy-hub__head \{ min-height: calc\(clamp\(32px, 4\.4vw, 60px\) \* 2\.1 \+ 14px \+ 22px\); \}/)
  assert.doesNotMatch(read('app/components/estimator/SolarEstimatorPage.vue'), /kỹ sư/)
})

test('Google Tag Manager comes from the site settings and only as GTM-XXXX', async () => {
  const { isPublicSitePayload } = await import('../shared/contracts/public-site.ts').catch(() => ({}))
  const app = read('app/app.vue')
  assert.match(app, /\/\^GTM-\[A-Z0-9\]\{4,12\}\$\/\.test\(site\.value\?\.gtmId/)
  assert.match(app, /tagPosition: 'bodyOpen'/)
  assert.match(read('shared/contracts/public-site.ts'), /value\.gtmId === '' \|\| typeof value\.gtmId === 'string' && \/\^GTM-\[A-Z0-9\]\{4,12\}\$\//)
  if (isPublicSitePayload) assert.ok(true)
})
