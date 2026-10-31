const test = require('node:test')
const assert = require('node:assert/strict')
const {
  auditManifest,
  parseArgs,
  previewPathToPageFile,
  renderMarkdown,
  validateManifest,
} = require('../scripts/audit-nuxt-progress.cjs')

test('maps preview paths with query strings to Nuxt page files', () => {
  assert.equal(previewPathToPageFile('/migration-product-preview?slug=lamp'), 'frontend/app/pages/migration-product-preview.vue')
  assert.equal(previewPathToPageFile('/giai-phap/'), null)
})

test('reports manifest coverage and unmanifested preview pages', () => {
  const report = auditManifest({ routes: [
    { id: 'home', legacyPath: '/', previewPath: '/migration-home-preview' },
    { id: 'catalog', legacyPath: '/san-pham/', previewPath: '/migration-catalog-preview' },
  ] }, new Set([
    'frontend/app/pages/migration-home-preview.vue',
    'frontend/app/pages/migration-catalog-preview.vue',
    'frontend/app/pages/migration-ui-lab-preview.vue',
  ]))

  assert.equal(report.summary.manifestRoutesWithPages, 2)
  assert.equal(report.summary.manifestRoutesMissingPages, 0)
  assert.deepEqual(report.unmanifestedPreviewPages, ['frontend/app/pages/migration-ui-lab-preview.vue'])
  assert.equal(report.scope.publicRouteCutoverProven, false)
})

test('marks a manifest route with no matching page', () => {
  const report = auditManifest({ routes: [{ id: 'news', previewPath: '/migration-news-preview' }] }, new Set())
  assert.equal(report.routes[0].status, 'preview-page-missing')
  assert.equal(report.summary.manifestRoutesMissingPages, 1)
})

test('rejects duplicate route ids and unsupported preview paths', () => {
  assert.throws(() => validateManifest({ routes: [
    { id: 'same', previewPath: '/migration-home-preview' },
    { id: 'same', previewPath: '/migration-catalog-preview' },
  ] }), /Duplicate route id/)
  for (const previewPath of ['https://example.test/migration-home-preview', '//example.test/migration-home-preview', '/nested/migration-home-preview', '/migration-home-preview/extra', '/migration-home-preview#section']) {
    assert.throws(() => validateManifest({ routes: [{ id: 'invalid-preview', previewPath }] }), /unsupported preview path/)
  }
})

test('allows aliases to share a preview page and checks coverage per route', () => {
  const manifest = { routes: [
    { id: 'about', legacyPath: '/gioi-thieu/', previewPath: '/migration-about-preview' },
    { id: 'legacy-about-alias', legacyPath: '/ve-chung-toi/', previewPath: '/migration-about-preview' },
  ] }
  const report = auditManifest(manifest, new Set(['frontend/app/pages/migration-about-preview.vue']))
  assert.equal(report.summary.manifestRoutesWithPages, 2)
  assert.equal(report.summary.manifestRoutesMissingPages, 0)
  assert.deepEqual(report.routes.map(route => route.status), ['preview-page-present', 'preview-page-present'])
})

test('parses root and output format without mutating files', () => {
  assert.deepEqual(parseArgs(['--root', '/tmp/nuxt-export', '--format', 'json']), {
    root: '/tmp/nuxt-export',
    manifest: 'tools/visual-tests/fixtures/migration-routes.json',
    format: 'json',
  })
})

test('renders the public-cutover limitation explicitly', () => {
  const markdown = renderMarkdown({
    scope: { publicRouteOwner: 'PHP' },
    summary: { manifestRoutes: 1, previewPagesFound: 1, manifestRoutesWithPages: 1, manifestRoutesMissingPages: 0, unmanifestedPreviewPages: 0 },
    routes: [{ id: 'home', previewPath: '/migration-home-preview', pageFile: 'frontend/app/pages/migration-home-preview.vue', status: 'preview-page-present' }],
    unmanifestedPreviewPages: [],
  }, '/tmp/nuxt-export', 'tools/visual-tests/fixtures/migration-routes.json')
  assert.match(markdown, /public route cutover proven: \*\*no\*\*/)
  assert.match(markdown, /does not approve SEO, visual, API, commerce, security, or production cutover parity/)
})
