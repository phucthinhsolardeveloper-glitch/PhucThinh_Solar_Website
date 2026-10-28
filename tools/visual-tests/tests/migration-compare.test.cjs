const test = require('node:test')
const assert = require('node:assert/strict')
const { parseArgs, parseViewport, validateOrigin, loadManifest, renderMarkdown, classifyDifference, previewLinkLeaks, viewportGate } = require('../scripts/migration-compare.cjs')

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
})

test('separates an aborted third-party map embed from a Nuxt UI error', () => {
  const base = { status: 200, navigationError: undefined, pageErrors: [], consoleMessages: [], failedRequests: [], horizontalOverflow: 0, fingerprints: { layout: 'same', text: 'a', media: 'a', links: 'a' }, title: 'A', h1: ['A'] }
  assert.equal(classifyDifference(base, { ...base, failedRequests: ['GET https://www.google.com/maps/embed?pb=fixture — net::ERR_ABORTED'] }, { status: 'compared', changedRatio: 0.2 }).kind, 'external-resource-blocked')
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
