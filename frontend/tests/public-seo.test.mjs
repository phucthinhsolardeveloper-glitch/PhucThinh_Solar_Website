import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { canonicalPublicOrigin, canonicalPublicUrl, isPublicSeoIndexable, publicSeoRobots, publicSocialImageUrl } from '../shared/public-seo.ts'
import { editorialPublicResponseStatus, editorialPublicRouteRobots } from '../shared/editorial-public-route.ts'

const frontendRoot = new URL('../', import.meta.url)
const read = (path) => readFileSync(new URL(path, frontendRoot), 'utf8')

test('canonical origin is taken only from the confirmed HTTPS site host', () => {
  assert.equal(canonicalPublicOrigin('https://phucthinhsolar.com'), 'https://phucthinhsolar.com')
  for (const origin of [
    'http://phucthinhsolar.com',
    'https://www.phucthinhsolar.com',
    'https://evil.example',
    'https://user@phucthinhsolar.com',
    'https://phucthinhsolar.com:8443',
    'https://phucthinhsolar.com/path',
    undefined,
  ]) assert.equal(canonicalPublicOrigin(origin), null)
})

test('canonical URL contains only a safe route path and never request-supplied origin/query', () => {
  assert.equal(canonicalPublicUrl('https://phucthinhsolar.com', '/giai-phap/solar/'), 'https://phucthinhsolar.com/giai-phap/solar/')
  for (const path of ['//evil.example/x', 'https://evil.example/x', '/a/../b', '/path?utm=1', '/path#hash', '/path\\evil', '']) {
    assert.equal(canonicalPublicUrl('https://phucthinhsolar.com', path), null)
  }
  assert.equal(canonicalPublicUrl('https://evil.example', '/safe/'), null)
})

test('social image uses an approved site URL and defaults only to the shipped favicon', () => {
  assert.equal(publicSocialImageUrl('https://phucthinhsolar.com', '/upload/news/story.jpg'), 'https://phucthinhsolar.com/upload/news/story.jpg')
  assert.equal(publicSocialImageUrl('https://phucthinhsolar.com', ''), 'https://phucthinhsolar.com/assets/images/favicon-pts-mobile.png')
  assert.equal(publicSocialImageUrl('https://phucthinhsolar.com', 'https://evil.example/image.jpg'), null)
})

test('filtered and empty public route states cannot be indexable', () => {
  assert.equal(isPublicSeoIndexable(true, true, false), true)
  assert.equal(isPublicSeoIndexable(true, true, true), false)
  assert.equal(isPublicSeoIndexable(true, false, false), false)
  assert.equal(isPublicSeoIndexable(false, true, false), false)
  assert.equal(publicSeoRobots(true, true), 'index, follow')
  assert.equal(publicSeoRobots(true, true, true), 'noindex, nofollow, noarchive')
  assert.equal(publicSeoRobots(true, false), 'noindex, nofollow, noarchive')
})

test('legacy product alias reuses flag-aware PDP redirect and does not duplicate page content', () => {
  const alias = read('app/pages/product/[slug].vue')
  const rootAlias = read('app/pages/product/index.vue')
  const aliasMiddleware = read('app/middleware/product-legacy-alias-public-route.ts')
  const rootMiddleware = read('app/middleware/product-root-alias-public-route.ts')
  assert.match(alias, /middleware:\s*productLegacyAliasPublicRoute/)
  assert.match(rootAlias, /middleware:\s*productRootAliasPublicRoute/)
  assert.match(aliasMiddleware, /_migration\/public-route-resolve/)
  assert.match(aliasMiddleware, /legacyProductDetailPublicPath\(slug\)/)
  assert.match(rootMiddleware, /LEGACY_CATALOG_PUBLIC_PATH/)
})

test('each renderable public content route wires the canonical and social metadata helper', () => {
  const owners = [
    'app/components/home/HomePageContent.vue',
    'app/pages/gioi-thieu.vue',
    'app/pages/giai-phap.vue',
    'app/pages/migration-solution-detail-preview.vue',
    'app/components/contact/ContactPageContent.vue',
    'app/components/dealer/DealerPageContent.vue',
    'app/components/catalog/CatalogPage.vue',
    'app/pages/san-pham/[slug].vue',
    'app/components/catalog/BrandDetailPage.vue',
    'app/components/catalog/SearchResultsPage.vue',
    'app/pages/migration-news-preview.vue',
    'app/pages/migration-news-detail-preview.vue',
    'app/pages/migration-projects-preview.vue',
    'app/pages/migration-project-detail-preview.vue',
    'app/pages/migration-gallery-preview.vue',
    'app/pages/migration-gallery-detail-preview.vue',
    'app/pages/migration-video-preview.vue',
    'app/pages/migration-policy-preview.vue',
  ]
  for (const path of owners) assert.match(read(path), /usePublicSocialMeta\(/, `${path} needs public metadata`)
})

test('public editorial metadata hides filtered, empty, and failed pages from indexing', () => {
  assert.equal(editorialPublicRouteRobots(true, true, true, false), 'index, follow')
  assert.equal(editorialPublicRouteRobots(true, true, true, true), 'noindex, nofollow, noarchive')
  assert.equal(editorialPublicRouteRobots(true, true, false, false), 'noindex, nofollow, noarchive')
  assert.equal(editorialPublicResponseStatus(true, 404, true), 404)
  assert.equal(editorialPublicResponseStatus(true, undefined, true), 502)
  assert.equal(editorialPublicResponseStatus(false, undefined, true), undefined)
})

test('Nuxt uses the source-verified canonical host and the reusable metadata composable', () => {
  const config = read('nuxt.config.ts')
  const composable = read('app/composables/usePublicSocialMeta.ts')
  assert.match(config, /canonicalSiteOrigin:\s*'https:\/\/phucthinhsolar\.com'/)
  assert.match(composable, /rel:\s*'canonical'/)
  assert.match(composable, /ogTitle:/)
  assert.match(composable, /ogDescription:/)
  assert.match(composable, /ogUrl:/)
  assert.match(composable, /ogImage:/)
  assert.match(composable, /twitterCard:/)
})
