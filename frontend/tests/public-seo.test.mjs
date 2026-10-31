import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { canonicalPublicOrigin, canonicalPublicUrl, isPublicSeoIndexable, publicBreadcrumbStructuredData, publicSeoDescription, publicSeoRobots, publicSeoTitle, publicSiteStructuredData, publicSocialImageUrl } from '../shared/public-seo.ts'
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

test('social image uses an approved site URL and falls back to the PHP public logo', () => {
  assert.equal(publicSocialImageUrl('https://phucthinhsolar.com', '/upload/news/story.jpg'), 'https://phucthinhsolar.com/upload/news/story.jpg')
  assert.equal(publicSocialImageUrl('https://phucthinhsolar.com', ''), 'https://phucthinhsolar.com/upload/photo/logo-phucthinh-solar-wordmark-2026.png')
  assert.equal(publicSocialImageUrl('https://phucthinhsolar.com', 'https://evil.example/image.jpg'), null)
})

test('public page titles add the PHP brand suffix once, including truncated legacy suffixes', () => {
  assert.equal(publicSeoTitle('Tin mới nhất'), 'Tin mới nhất | Phúc Thịnh Solar')
  assert.equal(publicSeoTitle('  Tin mới nhất  '), 'Tin mới nhất | Phúc Thịnh Solar')
  assert.equal(publicSeoTitle('Tin mới nhất | Phúc Thịnh Solar'), 'Tin mới nhất | Phúc Thịnh Solar')
  assert.equal(publicSeoTitle('Tin mới nhất | Phúc Thịnh So'), 'Tin mới nhất | Phúc Thịnh So')
  assert.equal(publicSeoTitle('', 'Video'), 'Video | Phúc Thịnh Solar')
})

test('empty social descriptions use the PHP site-setting fallback instead of only the brand label', () => {
  assert.equal(publicSeoDescription('  Mô tả CMS  ', 'Tên doanh nghiệp'), 'Mô tả CMS')
  assert.equal(publicSeoDescription('', ' Tên doanh nghiệp '), 'Tên doanh nghiệp')
  assert.equal(publicSeoDescription('', ''), 'Phúc Thịnh Solar')
  assert.equal(publicSeoDescription(null, undefined), 'Phúc Thịnh Solar')
})

test('breadcrumb JSON-LD mirrors the rendered route labels and rejects unsafe destinations', () => {
  const raw = publicBreadcrumbStructuredData('https://phucthinhsolar.com', '/tin-moi-nhat', [
    { label: 'Kiến thức', path: '/tin-tuc/' },
    { label: 'Tin mới nhất' },
  ])
  const data = JSON.parse(raw)
  assert.equal(data['@type'], 'BreadcrumbList')
  assert.deepEqual(data.itemListElement.map(({ name, item }) => [name, item]), [
    ['Trang chủ', 'https://phucthinhsolar.com/'],
    ['Kiến thức', 'https://phucthinhsolar.com/tin-tuc/'],
    ['Tin mới nhất', 'https://phucthinhsolar.com/tin-moi-nhat'],
  ])
  assert.equal(publicBreadcrumbStructuredData('https://phucthinhsolar.com', '/tin-moi-nhat', [{ label: 'X', path: 'https://evil.example/' }]), null)
  assert.ok(publicBreadcrumbStructuredData('https://phucthinhsolar.com', '/tin-moi-nhat', [{ label: '<script>' }]).includes('\\u003cscript>'))
})

test('Organization and homepage WebSite JSON-LD use only the canonical host and public CMS fields', () => {
  const site = {
    schemaVersion: 1,
    brand: { name: 'Phúc Thịnh Solar', hotline: '0944 555 567', logo: '/upload/photo/logo.webp' },
    productCategories: [],
    footer: {
      companyName: '',
      contactText: { format: 'sanitized-html', html: '', sanitizedBy: 'pts_public_richtext', sanitizerVersion: 1 },
      policies: [],
      locations: [{ name: 'Trụ sở chính', address: 'Gia Lai', mapUrl: '' }],
      socialLinks: [{ label: 'Facebook', href: 'https://facebook.com/pts' }, { label: 'Google', href: 'https://google.com' }],
    },
  }
  const about = JSON.parse(publicSiteStructuredData('https://phucthinhsolar.com', '/gioi-thieu/', site))
  assert.equal(about['@type'], 'Organization')
  assert.equal(about['@id'], 'https://phucthinhsolar.com/#organization')
  assert.equal(about.logo.url, 'https://phucthinhsolar.com/upload/photo/logo.webp')
  assert.equal(about.telephone, '0944 555 567')
  assert.deepEqual(about.sameAs, ['https://facebook.com/pts'])
  assert.equal(about.address.streetAddress, 'Gia Lai')
  assert.equal('email' in about, false)

  const homeJson = publicSiteStructuredData('https://phucthinhsolar.com', '/', site)
  const home = JSON.parse(homeJson)
  assert.deepEqual(home['@graph'].map((node) => node['@type']), ['Organization', 'WebSite'])
  assert.equal(home['@graph'][1].publisher['@id'], 'https://phucthinhsolar.com/#organization')
  assert.equal(publicSiteStructuredData('https://evil.example', '/', site), null)
  const dangerousCmsText = publicSiteStructuredData('https://phucthinhsolar.com', '/gioi-thieu/', {
    ...site,
    brand: { ...site.brand, hotline: '<script>alert(1)</script>' },
  })
  assert.doesNotMatch(dangerousCmsText, /<script>/)
  assert.match(dangerousCmsText, /\\u003cscript>/)
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

test('legacy product aliases resolve to Nuxt canonical routes and do not duplicate page content', () => {
  const alias = read('app/pages/product/[slug].vue')
  const rootAlias = read('app/pages/product/index.vue')
  const aliasMiddleware = read('app/middleware/product-legacy-alias-public-route.ts')
  const rootMiddleware = read('app/middleware/product-root-alias-public-route.ts')
  assert.match(alias, /middleware:\s*productLegacyAliasPublicRoute/)
  assert.match(rootAlias, /middleware:\s*productRootAliasPublicRoute/)
  assert.match(aliasMiddleware, /_migration\/public-route-resolve/)
  assert.doesNotMatch(aliasMiddleware, /PublicRouteEnabled|legacyProductDetailPublicPath|phpPublicRouteUrl/)
  assert.match(aliasMiddleware, /_migration\/public-route-resolve/)
  assert.match(rootMiddleware, /navigateTo\(CATALOG_PUBLIC_PATH, \{ redirectCode: 301 \}\)/)
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

test('shared public shell renders the canonical Organization schema from the PHP site DTO', () => {
  const header = read('app/components/SiteHeader.vue')
  assert.match(header, /publicSiteStructuredData\(config\.public\.canonicalSiteOrigin, route\.path, props\.site\)/)
  assert.match(header, /key: 'pts-public-organization-schema'/)
})
