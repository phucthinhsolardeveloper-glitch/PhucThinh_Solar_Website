import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { projectCategoryPublicPath } from '../shared/public-route-gate.ts'
import { projectCategoryCanonicalPath, projectCategoryPage } from '../shared/project-category-route.ts'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('project category paths are validated', () => {
  assert.equal(projectCategoryPublicPath('ho-gia-dinh'), '/danh-muc-du-an/ho-gia-dinh/')
  assert.throws(() => projectCategoryPublicPath('../admin'), /Invalid project category slug/)
})

test('Nuxt project category page parsing accepts only bounded positive pages', () => {
  assert.equal(projectCategoryPage('12'), 12)
  assert.equal(projectCategoryPage('0'), 1)
  assert.equal(projectCategoryPage('10001'), 1)
  assert.equal(projectCategoryPage(['2']), 1)
  assert.equal(projectCategoryCanonicalPath('du-an-ca-nhan'), '/du-an-ca-nhan')
  assert.throws(() => projectCategoryCanonicalPath('../private'), /Invalid project category slug/)
})

test('project category route is Nuxt-owned, uses category API, and preserves CMS 404 status', () => {
  const middleware = read('../app/middleware/project-category-public-route.ts')
  const page = read('../app/components/projects/ProjectCategoryPage.vue')
  const routePage = read('../app/pages/danh-muc-du-an/[slug].vue')
  const projectsAdapter = read('../server/routes/_api/projects.get.ts')
  assert.doesNotMatch(middleware, /PublicRouteEnabled|phpPublicRouteUrl/)
  assert.match(middleware, /to\.path !== canonicalPath/)
  assert.match(page, /category: slug\.value, page: String\(page\.value\)/)
  assert.match(page, /payload\?\.category\?\.slug === slug\.value/)
  assert.match(page, /const title = projects\.value\?\.category\?\.name \|\| projects\.value\?\.intro\.title/)
  assert.match(page, /`Dự án \$\{title\}`/)
  assert.match(page, /const canonicalPath = computed\(\(\) => projects\.value\?\.category\?\.canonicalPath/)
  assert.match(page, /canonicalPath,/)
  assert.match(page, /publicSeoTitle\([\s\S]*projects\.value\?\.category\?\.seo\?\.title \|\| projects\.value\?\.category\?\.name/)
  assert.match(page, /projects\.value\?\.category\?\.seo\?\.description \|\| categoryDescription\.value/)
  assert.match(page, /category\?\.seo\?\.image \|\| projects\.value\?\.category\?\.image/)
  assert.match(page, /editorialPublicResponseStatus\(true, errorStatus, !projects\.value\)/)
  assert.match(routePage, /ProjectCategoryPage/)
  assert.match(projectsAdapter, /passThroughStatuses:\s*\[400, 404\]/)

})
