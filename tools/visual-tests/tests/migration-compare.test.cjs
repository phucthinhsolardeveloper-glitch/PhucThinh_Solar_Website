const test = require('node:test')
const assert = require('node:assert/strict')
const migrationRoutes = require('../fixtures/migration-routes.json')
const { parseArgs, parseViewport, validateOrigin, loadManifest, renderMarkdown, classifyDifference, previewLinkLeaks, viewportGate, hasActionableBrokenMedia } = require('../scripts/migration-compare.cjs')

test('accepts loopback origins and rejects remote origins by default', () => {
  assert.equal(validateOrigin('http://127.0.0.1:8080/'), 'http://127.0.0.1:8080')
  assert.throws(() => validateOrigin('https://example.test'), /Remote URL blocked/)
  assert.equal(validateOrigin('https://example.test', true), 'https://example.test')
})

test('rejects credentials and credential-like query parameters', () => {
  assert.throws(() => validateOrigin('http://user:pass@127.0.0.1:8080'), /embedded credentials/)
  assert.throws(() => validateOrigin('http://127.0.0.1:8080/?access_token=x'), /Credential-like/)
})

test('parses explicit viewport and two URL CLI contract', () => {
  assert.deepEqual(parseViewport('390x844'), { width: 390, height: 844 })
  const args = parseArgs(['http://127.0.0.1:8080', 'http://127.0.0.1:3100', '--viewport', '768x900'])
  assert.deepEqual(args.viewports, [{ width: 768, height: 900 }])
  assert.throws(() => parseArgs(['http://127.0.0.1:8080']), /Exactly two URLs/)
})

test('uses the required three responsive default viewports', () => {
  const args = parseArgs(['http://127.0.0.1:8080', 'http://127.0.0.1:3100'])
  assert.deepEqual(args.viewports, [{ width: 390, height: 844 }, { width: 768, height: 900 }, { width: 1440, height: 900 }])
})

test('validates explicit route mapping and duplicate ids', () => {
  const manifest = loadManifest({ routes: [{ id: 'solutions', legacyPath: '/giai-phap/', previewPath: '/migration-preview' }] })
  assert.equal(manifest.routes[0].previewPath, '/migration-preview')
  assert.throws(() => loadManifest({ routes: [{ id: 'x', legacyPath: '/', previewPath: '/' }, { id: 'x', legacyPath: '/', previewPath: '/' }] }), /Duplicate route id/)
})

test('visual comparison covers every route in the migration manifest', () => {
  const manifest = loadManifest(migrationRoutes)
  assert.equal(manifest.routes.length, 35)
  assert.deepEqual(manifest.routes.filter(route => route.visual === false).map(route => route.id), [])
})

test('keeps absolute route targets behind the same remote safety switch', () => {
  const args = parseArgs(['http://127.0.0.1:8080', 'http://127.0.0.1:3100'])
  assert.equal(args.allowRemote, undefined)
  assert.throws(() => validateOrigin('https://example.test/solution', args.allowRemote), /Remote URL blocked/)
})

test('renders missing preview and heuristic diff limitations into markdown', () => {
  const markdown = renderMarkdown({
    generatedAt: '2026-10-26T00:00:00.000Z', legacyUrl: 'http://127.0.0.1:8080', previewUrl: 'http://127.0.0.1:3100', browser: 'chromium', reducedMotion: 'reduce', viewports: [{ width: 390, height: 844 }],
    routes: [{ id: 'solutions', viewports: [{ width: 390, height: 844, legacy: { status: 200, horizontalOverflow: 0 }, preview: { status: null, navigationError: 'missing', horizontalOverflow: null }, diff: { status: 'not-compared' } }] }], interactions: [],
  })
  assert.match(markdown, /preview route unavailable/)
  assert.match(markdown, /not a pixel-perfect gate/)
})

test('reports that full-page captures are manual review images without an automatic pixel diff', () => {
  const markdown = renderMarkdown({
    generatedAt: '2026-10-29T00:00:00.000Z', legacyUrl: 'http://127.0.0.1:8080', previewUrl: 'http://127.0.0.1:3110', browser: 'chromium', reducedMotion: 'reduce', fullPage: true, viewports: [], routes: [], interactions: [],
  })
  assert.match(markdown, /Full-page screenshots are captured for manual section-by-section review; no pixel diff is run/)
  assert.doesNotMatch(markdown, /first-fold viewport/)
})

test('classifies same-geometry content changes separately from layout changes', () => {
  const base = { status: 200, navigationError: undefined, pageErrors: [], consoleMessages: [], horizontalOverflow: 0, fingerprints: { layout: 'same', text: 'a', media: 'a', links: 'a' }, title: 'A', h1: ['A'] }
  const content = { ...base, fingerprints: { ...base.fingerprints, text: 'b' } }
  assert.equal(classifyDifference(base, content, { status: 'compared', changedRatio: 0.2 }).kind, 'dynamic-data-difference')
  const layout = { ...content, fingerprints: { ...content.fingerprints, layout: 'different' } }
  assert.equal(classifyDifference(base, layout, { status: 'compared', changedRatio: 0.2 }).kind, 'mixed-data-and-ui-difference')
})

test('promotes failed media requests to a runtime/media gap', () => {
  const base = { status: 200, navigationError: undefined, pageErrors: [], consoleMessages: [], failedRequests: [], horizontalOverflow: 0, fingerprints: { layout: 'same', text: 'a', media: 'a', links: 'a' }, title: 'A', h1: ['A'] }
  assert.equal(classifyDifference(base, { ...base, failedRequests: ['GET /missing.webp'] }, { status: 'compared', changedRatio: 0.2 }).kind, 'runtime-or-route-error')
  assert.equal(classifyDifference(base, { ...base, brokenMedia: [{ src: '/broken-dom-image.webp' }] }, { status: 'compared', changedRatio: 0.2 }).kind, 'runtime-or-route-error')
})

test('ignores hidden or empty-source media as broken-media failures', () => {
  const base = { status: 200, navigationError: undefined, pageErrors: [], consoleMessages: [], failedRequests: [], horizontalOverflow: 0, fingerprints: { layout: 'same', text: 'a', media: 'a', links: 'a' }, title: 'A', h1: ['A'] }
  const inactiveWidgetMedia = [{ src: '', alt: 'vi' }, { src: '/hidden-flag.svg', visible: false }]
  assert.equal(hasActionableBrokenMedia(inactiveWidgetMedia), false)
  assert.equal(classifyDifference(base, { ...base, brokenMedia: inactiveWidgetMedia }, { status: 'compared', changedRatio: 0 }).kind, 'stable-or-rendering-noise')
  assert.equal(hasActionableBrokenMedia([{ src: '/visible-broken.webp', visible: true }]), true)
})

test('separates an aborted third-party map embed from a Nuxt UI error', () => {
  const base = { status: 200, navigationError: undefined, pageErrors: [], consoleMessages: [], failedRequests: [], horizontalOverflow: 0, fingerprints: { layout: 'same', text: 'a', media: 'a', links: 'a' }, title: 'A', h1: ['A'] }
  assert.equal(classifyDifference(base, { ...base, failedRequests: ['GET https://www.google.com/maps/embed?pb=fixture — net::ERR_ABORTED'] }, { status: 'compared', changedRatio: 0.2 }).kind, 'external-resource-blocked')
})

test('PHP reference failures do not classify a healthy Nuxt preview as a runtime error', () => {
  const base = { status: 200, navigationError: undefined, pageErrors: [], consoleMessages: [], failedRequests: [], horizontalOverflow: 0, cumulativeLayoutShift: 0, fingerprints: { layout: 'same', text: 'a', media: 'a', links: 'a' }, title: 'A', h1: ['A'] }
  const legacy = { ...base, pageErrors: ['legacy page error'], consoleMessages: ['legacy console error'], failedRequests: ['GET /legacy-missing.webp'], brokenMedia: [{ src: '/legacy-missing.webp' }], horizontalOverflow: 12, cumulativeLayoutShift: 0.24 }
  assert.equal(classifyDifference(legacy, base, { status: 'compared', changedRatio: 0 }).kind, 'stable-or-rendering-noise')
})

test('detects preview links that leak to public PHP paths', () => {
  const settings = { legacyUrl: 'http://127.0.0.1:8088', previewUrl: 'http://127.0.0.1:3110' }
  const preview = { hrefs: [
    'http://127.0.0.1:3110/migration-home-preview',
    'http://127.0.0.1:3110/_nuxt/app.js',
    'http://127.0.0.1:3110/san-pham/',
    'http://127.0.0.1:8088/giai-phap/',
    'https://example.test/',
  ] }
  assert.deepEqual(previewLinkLeaks(preview, settings), [
    'http://127.0.0.1:3110/san-pham/',
    'http://127.0.0.1:8088/giai-phap/',
  ])
})

test('public-route mode allows same-origin preview and canonical Nuxt links', () => {
  const settings = { legacyUrl: 'http://127.0.0.1:8088', previewUrl: 'http://127.0.0.1:3110', publicRoutes: true }
  const preview = { hrefs: [
    'http://127.0.0.1:3110/migration-home-preview',
    'http://127.0.0.1:3110/san-pham/',
    'http://127.0.0.1:8088/giai-phap/',
  ] }
  assert.deepEqual(previewLinkLeaks(preview, settings), ['http://127.0.0.1:8088/giai-phap/'])
})

test('requires runtime, media, responsive, heading, noindex and link gates', () => {
  const settings = { legacyUrl: 'http://127.0.0.1:8088', previewUrl: 'http://127.0.0.1:3110' }
  const base = {
    status: 200, navigationError: undefined, pageErrors: [], consoleMessages: [], failedRequests: [],
    brokenMedia: [], horizontalOverflow: 0, cumulativeLayoutShift: 0, hrefs: [], title: 'Page', h1: ['Page'], robots: '',
  }
  const passing = viewportGate(base, { ...base, robots: 'noindex, nofollow' }, settings)
  assert.equal(passing.status, 'PASS')
  const failing = viewportGate(base, { ...base, robots: 'index, follow', brokenMedia: [{ src: '/missing.webp' }] }, settings)
  assert.equal(failing.status, 'FAIL')
  assert.equal(failing.checks.media, false)
  assert.equal(failing.checks.previewNoindex, false)
})

test('renders PHP reference diagnostics separately from Nuxt acceptance gaps', () => {
  const settings = { legacyUrl: 'http://127.0.0.1:8088', previewUrl: 'http://127.0.0.1:3110' }
  const preview = {
    status: 200, navigationError: undefined, pageErrors: [], consoleMessages: [], failedRequests: [], brokenMedia: [],
    horizontalOverflow: 0, cumulativeLayoutShift: 0, hrefs: [], title: 'Page', h1: ['Page'], robots: 'noindex',
  }
  const legacy = {
    status: 200, navigationError: undefined, pageErrors: [], consoleMessages: [], consoleWarnings: ['legacy console warning'], failedRequests: ['GET /legacy-broken.webp'],
    brokenMedia: [{ src: '/legacy-broken.webp' }], horizontalOverflow: 12, cumulativeLayoutShift: 0.24,
  }
  const gate = viewportGate(legacy, preview, settings)
  const markdown = renderMarkdown({
    generatedAt: '2026-10-28T00:00:00.000Z', legacyUrl: settings.legacyUrl, previewUrl: settings.previewUrl,
    browser: 'chromium', reducedMotion: 'reduce', viewports: [{ width: 390, height: 844 }],
    routes: [{ id: 'page', viewports: [{ width: 390, height: 844, legacy, preview, gate, diff: { status: 'not-compared' } }] }],
    interactions: [],
  })
  assert.match(markdown, /\| Route \| Viewport \| Gate \| Preview HTTP \| Classification \| Changed pixels \| Nuxt gaps \| PHP reference issues \|/)
  assert.match(markdown, /\| page \| 390x844 \| PASS \| 200 \| unknown \| — \| — \| console warnings 1; failed requests 1; broken media 1; overflow 12px; CLS 0\.240 \|/)
})

test('PHP reference failures do not fail an otherwise healthy Nuxt preview', () => {
  const settings = { legacyUrl: 'http://127.0.0.1:8088', previewUrl: 'http://127.0.0.1:3110' }
  const preview = {
    status: 200, navigationError: undefined, pageErrors: [], consoleMessages: [], failedRequests: [],
    brokenMedia: [], horizontalOverflow: 0, cumulativeLayoutShift: 0.02, hrefs: [], title: 'Page', h1: ['Page'], robots: 'noindex, nofollow',
  }
  const legacy = {
    status: 200, navigationError: undefined, pageErrors: [], consoleMessages: [], failedRequests: ['GET /legacy-broken.webp'],
    brokenMedia: [{ src: '/legacy-broken.webp' }], horizontalOverflow: 12, cumulativeLayoutShift: 0.24,
  }
  const result = viewportGate(legacy, preview, settings)
  assert.equal(result.status, 'PASS')
  assert.equal(result.checks.media, true)
  assert.equal(result.checks.horizontalOverflow, true)
  assert.equal(result.checks.responsiveStability, true)
  assert.deepEqual(result.referenceDiagnostics, {
    runtimeErrors: 0,
    consoleWarnings: 0,
    status: 200,
    navigationError: null,
    brokenMedia: 1,
    actionableFailedRequests: 1,
    horizontalOverflow: 12,
    cumulativeLayoutShift: 0.24,
  })
})

test('PHP HTTP and navigation failures remain reference diagnostics, not Nuxt gate failures', () => {
  const settings = { legacyUrl: 'http://127.0.0.1:8088', previewUrl: 'http://127.0.0.1:3110' }
  const preview = {
    status: 200, navigationError: undefined, pageErrors: [], consoleMessages: [], consoleWarnings: [], failedRequests: [],
    brokenMedia: [], horizontalOverflow: 0, cumulativeLayoutShift: 0, hrefs: [], title: 'Page', h1: ['Page'], robots: 'noindex',
  }
  const legacy = { status: 404, navigationError: 'net::ERR_CONNECTION_RESET', pageErrors: [], consoleMessages: [], consoleWarnings: [], failedRequests: [], brokenMedia: [], horizontalOverflow: 0, cumulativeLayoutShift: 0 }
  const result = viewportGate(legacy, preview, settings)
  assert.equal(result.status, 'PASS')
  assert.equal(result.checks.previewHttp, true)
  assert.deepEqual(result.referenceDiagnostics, {
    runtimeErrors: 0, consoleWarnings: 0, status: 404, navigationError: 'net::ERR_CONNECTION_RESET',
    brokenMedia: 0, actionableFailedRequests: 0, horizontalOverflow: 0, cumulativeLayoutShift: 0,
  })
})

test('console warnings are diagnostic and do not fail the Nuxt runtime gate', () => {
  const settings = { legacyUrl: 'http://127.0.0.1:8088', previewUrl: 'http://127.0.0.1:3110' }
  const base = {
    status: 200, navigationError: undefined, pageErrors: [], consoleMessages: [], consoleWarnings: [], failedRequests: [],
    brokenMedia: [], horizontalOverflow: 0, cumulativeLayoutShift: 0, hrefs: [], title: 'Page', h1: ['Page'], robots: 'noindex',
  }
  const result = viewportGate({ ...base, consoleWarnings: ['legacy warning'] }, { ...base, consoleWarnings: ['preview warning'] }, settings)
  assert.equal(result.status, 'PASS')
  assert.equal(result.referenceDiagnostics.consoleWarnings, 1)
})

test('Nuxt preview media, overflow and CLS still fail their own gates', () => {
  const settings = { legacyUrl: 'http://127.0.0.1:8088', previewUrl: 'http://127.0.0.1:3110' }
  const base = {
    status: 200, navigationError: undefined, pageErrors: [], consoleMessages: [], failedRequests: [],
    brokenMedia: [], horizontalOverflow: 0, cumulativeLayoutShift: 0, hrefs: [], title: 'Page', h1: ['Page'], robots: 'noindex, nofollow',
  }
  const result = viewportGate(base, { ...base, brokenMedia: [{ src: '/preview-broken.webp' }], horizontalOverflow: 4, cumulativeLayoutShift: 0.2 }, settings)
  assert.equal(result.status, 'FAIL')
  assert.equal(result.checks.media, false)
  assert.equal(result.checks.horizontalOverflow, false)
  assert.equal(result.checks.responsiveStability, false)
})
