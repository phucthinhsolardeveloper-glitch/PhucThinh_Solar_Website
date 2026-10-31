import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import {
  POLICY_PUBLIC_ROOT,
  editorialDetailPublicPath,
  editorialPublicHubResponseStatus,
  editorialPublicResponseStatus,
  editorialPublicRouteRobots,
  isSafeEditorialSlug,
} from '../shared/editorial-public-route.ts'

const root = new URL('../', import.meta.url)
const read = (path) => readFileSync(new URL(path, root), 'utf8')

test('editorial public routes always render in Nuxt; SEO indexability remains separate', () => {
  assert.equal(editorialPublicRouteRobots(false, false), 'noindex, nofollow, noarchive')
  assert.equal(editorialPublicRouteRobots(undefined, true), 'noindex, nofollow, noarchive')
  assert.equal(editorialPublicRouteRobots(true, false), 'noindex, nofollow, noarchive')
  assert.equal(editorialPublicRouteRobots(true, 'true'), 'index, follow')
  assert.equal(editorialPublicRouteRobots(true, true), 'index, follow')
})

test('public editorial detail preserves upstream 404 and 502 while preview responses stay unchanged', () => {
  assert.equal(editorialPublicResponseStatus(true, 404), 404)
  assert.equal(editorialPublicResponseStatus(true, 502), 502)
  assert.equal(editorialPublicResponseStatus(true, 400), undefined)
  assert.equal(editorialPublicResponseStatus(false, 404), undefined)
  assert.equal(editorialPublicResponseStatus(false, 502), undefined)
  assert.match(read('app/pages/migration-solution-detail-preview.vue'), /setResponseStatus\(responseStatus/)
  assert.match(read('app/pages/migration-policy-preview.vue'), /setResponseStatus\(responseStatus/)
  assert.match(read('server/routes/_migration/policy-detail.get.ts'), /passThroughStatuses: \[404\]/)
})

test('public editorial hubs preserve not-found and set 502 for unavailable or invalid data', () => {
  assert.equal(editorialPublicHubResponseStatus(true, 404, true), 404)
  assert.equal(editorialPublicHubResponseStatus(true, 502, false), 502)
  assert.equal(editorialPublicHubResponseStatus(true, undefined, true), 502)
  assert.equal(editorialPublicHubResponseStatus(true, undefined, false), undefined)
  assert.equal(editorialPublicHubResponseStatus(false, 502, true), undefined)
  assert.equal(editorialPublicHubResponseStatus(false, undefined, true), undefined)
  for (const path of [
    'app/pages/migration-news-preview.vue',
    'app/pages/migration-projects-preview.vue',
    'app/pages/migration-gallery-preview.vue',
    'app/pages/migration-video-preview.vue',
    'app/pages/giai-phap.vue',
  ]) {
    assert.match(read(path), /editorialPublicHubResponseStatus/)
    assert.match(read(path), /setResponseStatus\(responseStatus, responseStatus === 404 \? 'Not Found' : 'Bad Gateway'\)/)
  }
})

test('editorial detail slugs build only safe solution and policy paths', () => {
  assert.equal(isSafeEditorialSlug('dien-mat-troi'), true)
  assert.equal(isSafeEditorialSlug('abc-123'), true)
  for (const slug of ['', '../etc/passwd', 'a/b', 'Uppercase', '-leading', 'trailing-', 'a--b', ['slug']]) {
    assert.equal(isSafeEditorialSlug(slug), false)
    assert.throws(() => editorialDetailPublicPath('/giai-phap/', slug))
  }
  assert.equal(editorialDetailPublicPath('/giai-phap/', 'dien-mat-troi'), '/giai-phap/dien-mat-troi/')
  assert.equal(editorialDetailPublicPath(POLICY_PUBLIC_ROOT, 'bao-hanh'), '/ho-tro/bao-hanh/')
  assert.throws(() => editorialDetailPublicPath('/unknown/', 'slug'))
})

test('static editorial wrappers reuse preview pages without runtime route flags or PHP fallback', () => {
  const routes = [
    ['tin-tuc/index.vue', 'migration-news-preview.vue', 'news-public-route', 'newsPublicRoute', 'NEWS_PUBLIC_PATH'],
    ['du-an-da-thuc-hien/index.vue', 'migration-projects-preview.vue', 'projects-public-route', 'projectsPublicRoute', 'PROJECTS_PUBLIC_PATH'],
    ['thu-vien-anh/index.vue', 'migration-gallery-preview.vue', 'gallery-public-route', 'galleryPublicRoute', 'GALLERY_PUBLIC_PATH'],
    ['video/index.vue', 'migration-video-preview.vue', 'video-public-route', 'videoPublicRoute', 'VIDEO_PUBLIC_PATH'],
  ]
  for (const [pagePath, previewPage, middlewareName, middlewareSymbol, pathConstant] of routes) {
    const page = read(`app/pages/${pagePath}`)
    const middleware = read(`app/middleware/${middlewareName}.ts`)
    assert.match(page, new RegExp(previewPage.replaceAll('.', '\\.')))
    assert.match(page, new RegExp(`middleware: ${middlewareSymbol}`))
    assert.doesNotMatch(middleware, /PublicRouteEnabled|phpPublicRouteUrl/)
    assert.match(middleware, new RegExp(`\\b${pathConstant}\\b`))
    assert.match(middleware, /redirectCode: 301/)
  }
})

test('dynamic editorial wrappers validate route slugs and remain Nuxt-owned', () => {
  const routes = [
    ['giai-phap/[slug].vue', 'migration-solution-detail-preview.vue', 'solution-detail-public-route'],
    ['ho-tro/[slug].vue', 'migration-policy-preview.vue', 'policy-detail-public-route'],
  ]
  for (const [pagePath, previewPage, middlewareName] of routes) {
    const page = read(`app/pages/${pagePath}`)
    const middleware = read(`app/middleware/${middlewareName}.ts`)
    assert.match(page, new RegExp(previewPage.replaceAll('.', '\\.')))
    assert.match(page, /definePageMeta\(\{ layout: false, middleware:/)
    assert.doesNotMatch(middleware, /PublicRouteEnabled|phpEditorialDetailUrl/)
    assert.match(middleware, /to\.params\.slug/)
    assert.match(middleware, /isSafeEditorialSlug\(slug\)/)
    assert.match(middleware, /editorialDetailPublicPath/)
    assert.match(middleware, /redirectCode: 301/)
  }

  for (const middlewarePath of ['app/middleware/solution-detail-public-route.ts', 'app/middleware/policy-detail-public-route.ts']) {
    const middleware = read(middlewarePath)
    assert.match(middleware, /editorialDetailPublicPath/)
    assert.match(middleware, /to\.path !== canonicalPath[\s\S]*navigateTo\(canonicalPath, \{ redirectCode: 301 \}\)/)
  }

  const solutionsHubMiddleware = read('app/middleware/solutions-public-route.ts')
  assert.match(solutionsHubMiddleware, /to\.path !== SOLUTIONS_PUBLIC_PATH[\s\S]*return/)
})

test('public wrappers use separate SEO indexability settings; preview URLs remain noindex', () => {
  const pages = [
    ['migration-news-preview.vue', 'newsPublicRouteIndexable'],
    ['migration-projects-preview.vue', 'projectsPublicRouteIndexable'],
    ['migration-gallery-preview.vue', 'galleryPublicRouteIndexable'],
    ['migration-video-preview.vue', 'videoPublicRouteIndexable'],
    ['migration-solution-detail-preview.vue', 'solutionDetailPublicRouteIndexable'],
    ['migration-policy-preview.vue', 'policyDetailPublicRouteIndexable'],
  ]
  for (const [path, indexableFlag] of pages) {
    const source = read(`app/pages/${path}`)
    assert.doesNotMatch(source, /PublicRouteEnabled/)
    assert.match(source, new RegExp(`config\\.public\\.${indexableFlag}`))
    assert.match(source, /editorialPublicRouteRobots/)
    assert.match(source, /isPublicRoute\.value|isBarePublicRoute\.value/)
  }
  assert.match(read('app/pages/migration-solution-detail-preview.vue'), /isSafeEditorialSlug\(slug\.value\)/)
  assert.match(read('app/pages/migration-policy-preview.vue'), /isSafeEditorialSlug\(slug\.value\)/)
})

test('public category, dealer, gallery, video and policy titles mirror PHP brand suffix rules', () => {
  for (const path of [
    'app/components/news/NewsCategoryPage.vue',
    'app/components/projects/ProjectCategoryPage.vue',
    'app/components/solutions/SolutionCategoryPage.vue',
    'app/components/dealer/DealerPageContent.vue',
    'app/pages/dai-ly/index.vue',
    'app/pages/migration-gallery-preview.vue',
    'app/pages/migration-video-preview.vue',
    'app/pages/migration-policy-preview.vue',
  ]) assert.match(read(path), /publicSeoTitle\(/, `${path} should apply PHP brand suffixes to public SEO titles`)
  assert.match(read('app/pages/migration-gallery-preview.vue'), /isPublicRoute\.value[\s\S]*publicSeoTitle/)
  assert.match(read('app/pages/migration-video-preview.vue'), /isPublicRoute\.value[\s\S]*publicSeoTitle/)
  assert.match(read('app/pages/migration-policy-preview.vue'), /isPublicRoute\.value[\s\S]*publicSeoTitle/)
})
