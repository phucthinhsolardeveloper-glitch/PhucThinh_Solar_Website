const test = require('node:test')
const assert = require('node:assert/strict')
const {
  parseArgs,
  validateOrigin,
  validateManifest,
  parseDocument,
  parseRobots,
  parseSitemap,
  noindexValue,
} = require('../scripts/migration-seo-audit.cjs')

test('allows loopback origins only by default and rejects credentials', () => {
  assert.equal(validateOrigin('http://127.0.0.1:8080/'), 'http://127.0.0.1:8080')
  assert.equal(validateOrigin('http://localhost:3100'), 'http://localhost:3100')
  assert.throws(() => validateOrigin('https://example.test'), /Remote origin blocked/)
  assert.throws(() => validateOrigin('http://user:pass@127.0.0.1:8080'), /embedded credentials/)
  assert.throws(() => validateOrigin('http://127.0.0.1:8080/?access_token=x'), /query parameters/)
})

test('parses safe CLI settings and rejects non-two-origin invocation', () => {
  const args = parseArgs(['http://127.0.0.1:8080', 'http://127.0.0.1:3100', '--timeout-ms', '5000', '--max-redirects', '4'])
  assert.equal(args.timeoutMs, 5000)
  assert.equal(args.maxRedirects, 4)
  assert.equal(args.allowRemote, false)
  assert.throws(() => parseArgs(['http://127.0.0.1:8080']), /Exactly two origins/)
  assert.throws(() => parseArgs(['http://127.0.0.1:8080', 'http://127.0.0.1:3100', '--timeout-ms', '0']), /between 1 and 120000/)
})

test('validates route manifest paths, ids, ownership and query safety', () => {
  const manifest = validateManifest({ routes: [{ id: 'brand-detail', legacyPath: '/thuong-hieu/longi/', previewPath: '/migration-brand-detail-preview?slug=longi', kind: 'brand-detail' }] })
  assert.equal(manifest.routes[0].publicOwner, 'php')
  assert.equal(manifest.routes[0].previewIndexing, 'noindex')
  assert.throws(() => validateManifest({ routes: [{ id: 'duplicate', legacyPath: '/', previewPath: '/' }, { id: 'duplicate', legacyPath: '/', previewPath: '/' }] }), /Duplicate route id/)
  assert.throws(() => validateManifest({ routes: [{ id: 'unsafe', legacyPath: '/?access_token=x', previewPath: '/' }] }), /credential-like/)
  assert.equal(validateManifest({ routes: [{ id: 'search', legacyPath: '/tim-kiem/?keyword=den', previewPath: '/migration-search-preview?keyword=den' }] }).routes[0].id, 'search')
  assert.throws(() => validateManifest({ routes: [{ id: 'bad-status', legacyPath: '/', previewPath: '/', expectedRedirectStatus: 200 }] }), /expectedRedirectStatus/)
  assert.throws(() => validateManifest({ routes: [{ id: 'remote', legacyPath: 'https://example.test/', previewPath: '/' }] }), /absolute-path reference/)
})

test('extracts title, headings, canonical, robots, OG, JSON-LD and internal hrefs', () => {
  const html = `<!doctype html><html><head>
    <title>Trang thử nghiệm</title>
    <meta name="robots" content="noindex, nofollow">
    <meta property="og:type" content="article"><meta property="og:title" content="OG title">
    <meta property="og:description" content="OG description"><meta property="og:url" content="http://127.0.0.1:3100/preview">
    <link rel="canonical" href="/canonical/">
    <script type="application/ld+json">{"@context":"https://schema.org","@graph":[{"@type":"Article"},{"@type":["BreadcrumbList","Thing"]}]}</script>
    </head><body><h1>Tiêu đề H1</h1><a href="/san-pham/">Sản phẩm</a><a href="https://outside.test/">Ngoài site</a></body></html>`
  const page = parseDocument(html, 'http://127.0.0.1:3100/preview')
  assert.equal(page.title, 'Trang thử nghiệm')
  assert.deepEqual(page.h1, ['Tiêu đề H1'])
  assert.equal(page.canonical, 'http://127.0.0.1:3100/canonical/')
  assert.equal(page.robots, 'noindex, nofollow')
  assert.equal(page.og.title, 'OG title')
  assert.deepEqual(page.jsonLd.types, ['Article', 'BreadcrumbList', 'Thing'])
  assert.deepEqual(page.hrefs.filter(item => item.internal).map(item => item.path), ['/san-pham/'])
})

test('parses robots and sitemap visibility without fetching listed URLs', () => {
  const robots = parseRobots('User-agent: *\nDisallow: /administrator/\nAllow: /\nSitemap: http://127.0.0.1:8080/sitemap.xml\n')
  assert.deepEqual(robots.disallow, ['/administrator/'])
  assert.deepEqual(robots.allow, ['/'])
  assert.deepEqual(robots.sitemaps, ['http://127.0.0.1:8080/sitemap.xml'])
  const sitemap = parseSitemap('<urlset><url><loc>http://127.0.0.1:8080/</loc></url><url><loc>/san-pham/</loc></url></urlset>')
  assert.equal(sitemap.kind, 'urlset')
  assert.equal(sitemap.count, 2)
  assert.equal(noindexValue('index,follow'), false)
  assert.equal(noindexValue('noindex, nofollow'), true)
})
