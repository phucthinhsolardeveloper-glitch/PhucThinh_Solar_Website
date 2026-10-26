const test = require('node:test')
const assert = require('node:assert/strict')
const { parseArgs, parseViewport, validateOrigin, loadManifest, renderMarkdown } = require('../scripts/migration-compare.cjs')

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
