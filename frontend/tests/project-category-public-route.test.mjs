import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { phpPublicRouteUrl, projectCategoryPublicPath } from '../shared/public-route-gate.ts'
import { legacyProjectCategoryQuery, projectCategoryCanonicalPath, projectCategoryPage } from '../shared/project-category-route.ts'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('legacy project category paths are validated and can safely fall back to PHP', () => {
  assert.equal(projectCategoryPublicPath('ho-gia-dinh'), '/danh-muc-du-an/ho-gia-dinh/')
  assert.equal(phpPublicRouteUrl('https://php.internal', projectCategoryPublicPath('ho-gia-dinh'), { p: '2' }), 'https://php.internal/danh-muc-du-an/ho-gia-dinh/?p=2')
  assert.throws(() => projectCategoryPublicPath('../admin'), /Invalid project category slug/)
  assert.throws(() => phpPublicRouteUrl('https://php.internal', '/danh-muc-du-an/a//b/'), /Unsupported public route fallback/)
})

test('flag-off redirect query and Nuxt API page parsing accept only bounded positive pages', () => {
  assert.deepEqual(legacyProjectCategoryQuery({ p: '3', category: 'ignored' }), { p: '3' })
  assert.deepEqual(legacyProjectCategoryQuery({ p: ['3'] }), {})
  assert.deepEqual(legacyProjectCategoryQuery({ p: '0' }), {})
  assert.equal(projectCategoryPage('12'), 12)
  assert.equal(projectCategoryPage('0'), 1)
  assert.equal(projectCategoryPage('10001'), 1)
  assert.equal(projectCategoryPage(['2']), 1)
  assert.equal(projectCategoryCanonicalPath('du-an-ca-nhan'), '/du-an-ca-nhan')
  assert.throws(() => projectCategoryCanonicalPath('../private'), /Invalid project category slug/)
})

test('project category route uses the projects public flag, category API, and preserves PHP 404 status', () => {
  const middleware = read('../app/middleware/project-category-public-route.ts')
  const page = read('../app/components/projects/ProjectCategoryPage.vue')
  const routePage = read('../app/pages/danh-muc-du-an/[slug].vue')
  const projectsApi = read('../../domains/phucthinhsolar.com/public_html/api/pts_v1_projects.php')
  const projectsDto = read('../../domains/phucthinhsolar.com/public_html/libraries/pts_public_projects_api.php')
  const projectsAdapter = read('../server/routes/_migration/projects.get.ts')
  const legacyRouter = read('../../domains/phucthinhsolar.com/public_html/libraries/router.php')
  const manifest = JSON.parse(read('../../tools/visual-tests/fixtures/migration-routes.json'))
  assert.match(middleware, /config\.public\.projectsPublicRouteEnabled/)
  assert.match(middleware, /phpPublicRouteUrl\(config\.public\.phpPublicOrigin, canonicalPath, legacyProjectCategoryQuery\(to\.query\)\)/)
  assert.match(page, /category: slug\.value, page: String\(page\.value\)/)
  assert.match(page, /payload\?\.category\?\.slug === slug\.value/)
  assert.match(page, /const title = projects\.value\?\.category\?\.name \|\| projects\.value\?\.intro\.title/)
  assert.match(page, /`Dự án \$\{title\}`/)
  assert.match(page, /const canonicalPath = computed\(\(\) => projects\.value\?\.category\?\.canonicalPath/)
  assert.match(page, /canonicalPath,/)
  assert.match(page, /projects\.value\?\.category\?\.seo\?\.title \|\| categoryTitle\.value/)
  assert.match(page, /projects\.value\?\.category\?\.seo\?\.description \|\| categoryDescription\.value/)
  assert.match(page, /category\?\.seo\?\.image \|\| projects\.value\?\.category\?\.image/)
  assert.match(page, /editorialPublicResponseStatus\(categoryRouteEnabled\.value, errorStatus, !projects\.value\)/)
  assert.match(routePage, /ProjectCategoryPage/)
  assert.match(projectsApi, /ptsProjectsError\(404, 'CATEGORY_NOT_FOUND'\)/)
  assert.match(projectsDto, /'canonicalPath' => '\/'\.\$slug/)
  assert.match(projectsAdapter, /passThroughStatuses:\s*\[400, 404\]/)
  assert.match(legacyRouter, /array\("tbl" => "news_list", "field" => "idl", "source" => "news", "com" => "du-an", "type" => "du-an"\)/)

  const categoryFixtures = manifest.routes.filter((route) => route.kind === 'project-category')
  assert.deepEqual(categoryFixtures.map(({ legacyPath, expectedCanonicalPath }) => [legacyPath, expectedCanonicalPath]), [
    ['/danh-muc-du-an/du-an-ca-nhan/', '/du-an-ca-nhan'],
    ['/danh-muc-du-an/du-an-doanh-nghiep/', '/du-an-doanh-nghiep'],
  ])
})
