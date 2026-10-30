import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('news category page uses the read-only category DTO, exact canonical, and no route feature flag', () => {
  const page = read('../app/components/news/NewsCategoryPage.vue')
  assert.match(page, /\$fetch\('\/_migration\/news', \{ query:/)
  assert.match(page, /category: slug\.value/)
  assert.match(page, /category\.value\?\.canonicalPath/)
  assert.doesNotMatch(page, /PublicRouteEnabled/)
  assert.match(page, /editorialPublicResponseStatus\(true,/)
  assert.match(page, /config\.public\.newsPublicRouteIndexable/)
  assert.match(page, /<h1[^>]*>{{ category\.name }}<\/h1>/)
})

test('solution category page uses the read-only filtered GET DTO as an always-on Nuxt route', () => {
  const page = read('../app/components/solutions/SolutionCategoryPage.vue')
  const adapter = read('../server/routes/_migration/solutions.get.ts')
  const api = read('../../domains/phucthinhsolar.com/public_html/api/pts_v1_solutions.php')
  assert.match(page, /\$fetch\('\/_migration\/solutions', \{ query: \{ category: slug\.value \} \}\)/)
  assert.match(page, /category\.value\?\.canonicalPath/)
  assert.doesNotMatch(page, /PublicRouteEnabled/)
  assert.match(page, /editorialPublicResponseStatus\(true,/)
  assert.match(page, /const links = useMigrationLinks\(\)/)
  assert.match(page, /:to="links\.generic\(item\.url\)"/)
  assert.match(adapter, /query: category === undefined \? \{\} : \{ category \}/)
  assert.match(adapter, /passThroughStatuses: \[400, 404\]/)
  assert.match(api, /CATEGORY_NOT_FOUND/)
  assert.match(api, /ptsPublicSolutionCategoryDto/)
})

test('editorial bare-slug routes render only the API-confirmed fixture categories and keep empty news group pending', () => {
  const manifest = JSON.parse(read('../../tools/visual-tests/fixtures/migration-routes.json'))
  const routes = manifest.routes.filter((route) => route.kind === 'news-category' || route.kind === 'solution-category')
  assert.deepEqual(routes.map((route) => route.legacyPath), [
    '/tin-moi-nhat', '/tin-truyen-thong', '/kien-thuc-san-pham', '/doanh-nghiep', '/ho-gia-dinh',
  ])
  assert.ok(routes.every((route) => route.expectedCanonicalPath === route.legacyPath))
  const empty = manifest.manualReviewRoutes.find((fixture) => fixture.slug === 'tin-tuyen-dung')
  assert.equal(empty.verification, 'NEEDS VERIFICATION')
  assert.equal(empty.family, 'news-category')
  assert.equal(empty.observedLegacyStatus, 200)
  assert.equal(manifest.negativeFixtures.some((fixture) => fixture.slug === 'tin-tuyen-dung'), false)
})

test('public category routes have no enable/disable flag and SEO indexability is separate', () => {
  const config = read('../nuxt.config.ts')
  assert.doesNotMatch(config, /PublicRouteEnabled/)
  assert.match(config, /newsPublicRouteIndexable:\s*true/)
  assert.match(config, /solutionsPublicRouteIndexable:\s*true/)
})
