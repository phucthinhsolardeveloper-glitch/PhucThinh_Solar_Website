const test = require('node:test')
const assert = require('node:assert/strict')
const http = require('node:http')
const {
  parseArgs,
  validateOrigin,
  validateManifest,
  parseDocument,
  parseRobots,
  parseSitemap,
  hrefMapping,
  hrefTargetSafetyReason,
  auditHrefTargets,
  evaluateNegativeFixture,
  auditSitemap,
  noindexValue,
  hasNoindex,
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

test('requires DB evidence for hidden fixtures and forbids invented NEEDS VERIFICATION URLs', () => {
  const manifest = validateManifest({
    routes: [{ id: 'home', legacyPath: '/', previewPath: '/' }],
    negativeFixtures: [
      { id: 'negative-hidden-product', family: 'pdp', verification: 'verified-hidden', slug: 'real-hidden-slug', legacyPath: '/san-pham/real-hidden-slug', databaseEvidence: { table: 'product', type: 'san-pham', status: '' } },
      { id: 'negative-missing-news', family: 'news', verification: 'NEEDS VERIFICATION', databaseEvidence: { table: 'news', type: 'tin-tuc', hiddenCount: 0 } },
    ],
  })
  assert.equal(manifest.negativeFixtures.length, 2)
  assert.equal(manifest.negativeFixtures[1].legacyPath, null)
  assert.throws(() => validateManifest({
    routes: [{ id: 'home', legacyPath: '/', previewPath: '/' }],
    negativeFixtures: [{ id: 'negative-made-up', family: 'news', verification: 'NEEDS VERIFICATION', slug: 'not-in-db', legacyPath: '/not-in-db' }],
  }), /must not contain an invented slug or route path/)
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

test('resolves relative hrefs and canonical against the declared document base', () => {
  const html = `<html><head><base href="http://127.0.0.1:8080/"><link rel="canonical" href="san-pham/"></head><body><a href="danh-muc-san-pham/den-nlmt/">Danh mục</a></body></html>`
  const page = parseDocument(html, 'http://127.0.0.1:8080/san-pham/')
  assert.equal(page.documentBase, 'http://127.0.0.1:8080/')
  assert.equal(page.canonical, 'http://127.0.0.1:8080/san-pham/')
  assert.deepEqual(page.hrefs.filter(item => item.internal).map(item => item.path), ['/danh-muc-san-pham/den-nlmt/'])
})

test('does not mark a relative href as internal when the declared document base is external', () => {
  const html = `<html><head><base href="https://external.test/"></head><body><a href="page/">External base</a><a href="http://127.0.0.1:8080/san-pham/">Local absolute</a></body></html>`
  const page = parseDocument(html, 'http://127.0.0.1:8080/san-pham/')
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
  assert.deepEqual(parseSitemap('<sm:sitemapindex><sm:sitemap><sm:loc>http://127.0.0.1:8080/nested.xml</sm:loc></sm:sitemap></sm:sitemapindex>'), {
    kind: 'index',
    locs: ['http://127.0.0.1:8080/nested.xml'],
    count: 1,
  })
  assert.equal(noindexValue('index,follow'), false)
  assert.equal(noindexValue('noindex, nofollow'), true)
})

test('treats an edge X-Robots-Tag noindex as effective when HTML meta is permissive', () => {
  assert.equal(hasNoindex('index, follow', 'noindex, nofollow, noarchive'), true)
  assert.equal(hasNoindex('noindex, nofollow', ''), true)
  assert.equal(hasNoindex('index, follow', ''), false)
})

test('negative fixture gate rejects soft-200, redirects and synthetic canonicals', () => {
  const fixture = { verification: 'verified-hidden', expectedStatuses: [404, 410] }
  const base = { redirectChain: [], page: { canonical: '' }, finalUrl: 'http://127.0.0.1/missing' }
  assert.equal(evaluateNegativeFixture(fixture, { ...base, status: 404 }).status, 'PASS')
  assert.equal(evaluateNegativeFixture(fixture, { ...base, status: 200 }).status, 'FAIL')
  assert.equal(evaluateNegativeFixture(fixture, { ...base, status: 404, page: { canonical: 'http://127.0.0.1/san-pham/fake' } }).status, 'FAIL')
  assert.equal(evaluateNegativeFixture({ verification: 'NEEDS VERIFICATION' }, null).status, 'NEEDS VERIFICATION')
})

test('href path differences are captured for manual review, not classified as automatic failures', () => {
  const mapping = hrefMapping(
    { hrefs: [{ internal: true, path: '/san-pham/' }, { internal: true, path: '/tin-tuc/' }] },
    { hrefs: [{ internal: true, path: '/migration-catalog-preview' }, { internal: true, path: '/tin-tuc/' }] },
  )
  assert.deepEqual(mapping.onlyLegacy, ['/san-pham/'])
  assert.deepEqual(mapping.onlyPreview, ['/migration-catalog-preview'])
  assert.equal(mapping.decision, 'manual-review-only')
})

test('href reachability uses read-only GET and skips sensitive or mutation-like paths', async t => {
  const requested = []
  const server = http.createServer((request, response) => {
    requested.push(request.url)
    if (request.url === '/redirect') { response.writeHead(301, { location: '/ok' }); response.end() }
    else if (request.url === '/redirect-logout') { response.writeHead(302, { location: '/logout' }); response.end() }
    else if (request.url === '/redirect-mutation-query') { response.writeHead(302, { location: '/?action=add' }); response.end() }
    else if (request.url === '/gone') { response.writeHead(404); response.end() }
    else { response.writeHead(200, { 'content-type': 'text/html' }); response.end('<html>ok</html>') }
  })
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve))
  t.after(() => new Promise(resolve => server.close(resolve)))

  const origin = `http://127.0.0.1:${server.address().port}`
  const audit = await auditHrefTargets([
    { hrefMapping: { onlyLegacy: ['/ok', '/redirect', '/gone', '/logout', '/api/internal', '/?token=secret', '/?action=add', '/?add-to-cart=42', '/redirect-logout', '/redirect-mutation-query'], onlyPreview: ['/ok'] } },
  ], origin, origin, { allowRemote: false, timeoutMs: 2000, maxRedirects: 5 })
  assert.equal(audit.targetCount, 11)
  assert.equal(audit.checkedCount, 4)
  assert.equal(audit.skippedCount, 7)
  assert.equal(audit.failedCount, 1)
  assert.equal(audit.failedTargets[0].href, '/gone')
  assert.equal(audit.status, 'FAIL')
  assert.deepEqual(requested.sort(), ['/gone', '/ok', '/ok', '/ok', '/redirect', '/redirect-logout', '/redirect-mutation-query'])
  assert.equal(hrefTargetSafetyReason('/api/private'), 'protected or API route')
  assert.equal(hrefTargetSafetyReason('/?token=secret'), 'credential-like query parameter')
  assert.equal(hrefTargetSafetyReason('/?action=add'), 'potential mutation action')
  assert.equal(hrefTargetSafetyReason('/?add-to-cart=42'), 'potential mutation query parameter')
  assert.equal(audit.skippedTargets.find(item => item.href === '/redirect-logout').reason, 'unsafe redirect target: potential mutation endpoint')
  assert.equal(audit.skippedTargets.find(item => item.href === '/redirect-mutation-query').reason, 'unsafe redirect target: potential mutation action')
})

test('sitemap crawler GETs every loc and fails URLs that redirect away from the declared loc', async t => {
  const server = http.createServer((request, response) => {
    if (request.url === '/nested.xml') {
      response.writeHead(200, { 'content-type': 'application/xml' })
      response.end(`<urlset><url><loc>http://127.0.0.1:${server.address().port}/ok</loc></url><url><loc>http://127.0.0.1:${server.address().port}/redirect</loc></url></urlset>`)
    } else if (request.url === '/ok') {
      response.writeHead(200, { 'content-type': 'text/html' }); response.end('<html>ok</html>')
    } else if (request.url === '/redirect') {
      response.writeHead(301, { location: '/ok' }); response.end()
    } else {
      response.writeHead(404); response.end()
    }
  })
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve))
  t.after(() => new Promise(resolve => server.close(resolve)))

  const origin = `http://127.0.0.1:${server.address().port}`
  const rootUrl = `${origin}/sitemap.xml`
  const rootResult = {
    status: 200,
    finalUrl: rootUrl,
    redirectChain: [],
    body: `<sitemapindex><sitemap><loc>${origin}/nested.xml</loc></sitemap></sitemapindex>`,
  }
  const audit = await auditSitemap(rootUrl, rootResult, { allowRemote: false, timeoutMs: 2000, maxRedirects: 5 })
  assert.equal(audit.status, 'FAIL')
  assert.equal(audit.sitemapCount, 2)
  assert.equal(audit.pageUrlCount, 2)
  assert.equal(audit.failedUrls.length, 1)
  assert.equal(audit.failedUrls[0].loc, `${origin}/redirect`)
  assert.equal(audit.failedUrls[0].finalUrl, `${origin}/ok`)
})
