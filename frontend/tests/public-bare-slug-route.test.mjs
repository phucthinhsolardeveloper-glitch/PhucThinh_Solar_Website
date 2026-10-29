import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { publicBareSlugDisposition } from '../shared/public-bare-slug-route.ts'
import { bareSlugPublicPath } from '../shared/public-route-gate.ts'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('public resolver dispositions preserve legacy-only fallback and canonical redirects', () => {
  assert.deepEqual(publicBareSlugDisposition({ schemaVersion: 1, slug: 'unclassified', kind: 'legacy-only', canonicalPath: null, reason: 'unsupported-route' }, {}), {
    mode: 'legacy', path: '/unclassified/',
  })
  assert.deepEqual(publicBareSlugDisposition({ schemaVersion: 1, slug: 'lamp', kind: 'pdp', canonicalPath: '/san-pham/lamp' }, {}), {
    mode: 'canonical', path: '/san-pham/lamp', statusCode: 301,
  })
  assert.deepEqual(publicBareSlugDisposition({ schemaVersion: 1, slug: 'lamp', kind: 'category', canonicalPath: '/danh-muc-san-pham/lamp/' }, {}), {
    mode: 'canonical', path: '/danh-muc-san-pham/lamp/', statusCode: 301,
  })
  assert.deepEqual(publicBareSlugDisposition({ schemaVersion: 1, slug: 'brand-a', kind: 'brand', canonicalPath: '/thuong-hieu/brand-a/' }, {}), {
    mode: 'canonical', path: '/thuong-hieu/brand-a/', statusCode: 301,
  })
  assert.deepEqual(publicBareSlugDisposition({ schemaVersion: 1, slug: 'solution-a', kind: 'solution', canonicalPath: '/giai-phap/solution-a/' }, {}), {
    mode: 'canonical', path: '/giai-phap/solution-a/', statusCode: 301,
  })
  assert.deepEqual(publicBareSlugDisposition({ schemaVersion: 1, slug: 'policy-a', kind: 'policy', canonicalPath: '/ho-tro/policy-a/' }, {}), {
    mode: 'canonical', path: '/ho-tro/policy-a/', statusCode: 301,
  })
})

test('news, project, and gallery render only when their detail flags are enabled', () => {
  for (const [kind, flag] of [['news', 'newsDetailPublicRouteEnabled'], ['project', 'projectDetailPublicRouteEnabled'], ['gallery', 'galleryDetailPublicRouteEnabled']]) {
    const slug = `${kind}-item`
    const result = { schemaVersion: 1, slug, kind, canonicalPath: `/${slug}/` }
    assert.deepEqual(publicBareSlugDisposition(result, { [flag]: false }), { mode: 'legacy', path: `/${slug}/` })
    assert.deepEqual(publicBareSlugDisposition(result, { [flag]: true }), { mode: 'render', kind })
  }
})

test('project-list categories render only when the projects public flag is enabled', () => {
  const result = { schemaVersion: 1, slug: 'du-an-ca-nhan', kind: 'project-category', canonicalPath: '/du-an-ca-nhan' }
  assert.deepEqual(publicBareSlugDisposition(result, { projectsPublicRouteEnabled: false }), { mode: 'legacy', path: '/du-an-ca-nhan/' })
  assert.deepEqual(publicBareSlugDisposition(result, { projectsPublicRouteEnabled: true }), { mode: 'render', kind: 'project-category' })
})

test('editorial categories render only behind their existing public hub flags', () => {
  const news = { schemaVersion: 1, slug: 'tin-moi-nhat', kind: 'news-category', canonicalPath: '/tin-moi-nhat' }
  const solution = { schemaVersion: 1, slug: 'ho-gia-dinh', kind: 'solution-category', canonicalPath: '/ho-gia-dinh' }
  assert.deepEqual(publicBareSlugDisposition(news, { newsPublicRouteEnabled: false }), { mode: 'legacy', path: '/tin-moi-nhat/' })
  assert.deepEqual(publicBareSlugDisposition(news, { newsPublicRouteEnabled: true }), { mode: 'render', kind: 'news-category' })
  assert.deepEqual(publicBareSlugDisposition(solution, { solutionsPublicRouteEnabled: false }), { mode: 'legacy', path: '/ho-gia-dinh/' })
  assert.deepEqual(publicBareSlugDisposition(solution, { solutionsPublicRouteEnabled: true }), { mode: 'render', kind: 'solution-category' })
})

test('bare route validates path before resolving and maps confirmed legacy aliases first', () => {
  const page = read('../app/pages/[slug].vue')
  assert.match(page, /'ve-chung-toi': '\/gioi-thieu\/'/)
  assert.match(page, /'he-thong-dai-ly': '\/lien-he\/'/)
  assert.match(page, /product: '\/san-pham\/'/)
  assert.ok(page.indexOf('const aliasTarget') < page.indexOf('useAsyncData'))
  assert.match(page, /const detailRoutesEnabled = isRuntimeFlagEnabled\(config\.public\.newsDetailPublicRouteEnabled\)/)
  assert.match(page, /isRuntimeFlagEnabled\(config\.public\.projectsPublicRouteEnabled\)/)
  assert.match(page, /isRuntimeFlagEnabled\(config\.public\.newsPublicRouteEnabled\)/)
  assert.match(page, /isRuntimeFlagEnabled\(config\.public\.solutionsPublicRouteEnabled\)/)
  assert.match(page, /\(\) => aliasTarget \|\| !pathIsSafe \|\| !pathCanResolve \|\| !detailRoutesEnabled/)
  assert.match(page, /const pathIsCanonical = pathIsSafe && route\.path === canonicalBarePath/)
  assert.match(page, /navigateTo\(canonicalBarePath, \{ redirectCode: 301 \}\)/)
  assert.match(page, /else if \(!detailRoutesEnabled && typeof canonicalBarePath === 'string'\)/)
  assert.match(page, /phpPublicRouteUrl\(config\.public\.phpPublicOrigin, canonicalBarePath\)/)
  assert.match(page, /editorialPublicResponseStatus\(true, resolutionStatus, true\)/)
  assert.match(page, /if \(!activeDetail\.value\) useSeoMeta\(\{ robots: 'noindex, nofollow, noarchive' \}\)/)
  assert.match(page, /bareSlugPublicPath\(slug\)/)
  assert.match(page, /phpPublicRouteUrl\(config\.public\.phpPublicOrigin, disposition\.path\)/)
  assert.match(page, /redirectCode: disposition\.statusCode/)
  assert.match(page, /ProjectCategoryPage/)
  assert.match(page, /NewsCategoryPage/)
  assert.match(page, /SolutionCategoryPage/)
  assert.match(page, /resolutionData\.value\.canonicalPath/)
  for (const reserved of ['ve-chung-toi', 'he-thong-dai-ly', 'product', 'giai-phap', 'tin-tuc', 'thu-vien-anh']) {
    assert.throws(() => bareSlugPublicPath(reserved))
  }
  assert.match(page, /reservedRouteRoots = new Set\(\['ho-tro'\]\)/)
})

test('public gallery links use bare canonical slugs while preview keeps its editor-safe URL', () => {
  const links = read('../app/composables/useMigrationLinks.ts')
  assert.match(links, /active\.value && SLUG\.test\(slug\) \? '\/migration-gallery-detail-preview\?slug='/)
  assert.match(links, /: '\/' \+ slug \+ '\/'/)
})

test('detail views consume a bare route slug and expose public indexability flags', () => {
  const pages = [
    ['../app/pages/migration-news-detail-preview.vue', 'newsDetailPublicRouteEnabled', 'newsDetailPublicRouteIndexable'],
    ['../app/pages/migration-project-detail-preview.vue', 'projectDetailPublicRouteEnabled', 'projectDetailPublicRouteIndexable'],
    ['../app/pages/migration-gallery-detail-preview.vue', 'galleryDetailPublicRouteEnabled', 'galleryDetailPublicRouteIndexable'],
  ]
  for (const [path, enabled, indexable] of pages) {
    const page = read(path)
    assert.match(page, /route\.params\.slug/)
    assert.match(page, new RegExp(`config\\.public\\.${enabled}`))
    assert.match(page, new RegExp(`config\\.public\\.${indexable}`))
    assert.match(page, /setResponseStatus\(responseStatus/)
  }
})
