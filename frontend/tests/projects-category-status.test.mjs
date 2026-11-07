import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('unknown project category keeps PHP CATEGORY_NOT_FOUND as a public 404', () => {
  const php = read('../../domains/phucthinhsolar.com/public_html/api/pts_v1_projects.php')
  const adapter = read('../server/routes/_api/projects.get.ts')
  const page = read('../app/pages/migration-projects-preview.vue')
  const helper = read('../shared/editorial-public-route.ts')

  assert.match(php, /ptsProjectsError\(404, 'CATEGORY_NOT_FOUND'\)/)
  assert.match(adapter, /passThroughStatuses:\s*\[400, 404\]/)
  assert.match(page, /editorialPublicHubResponseStatus\(publicProjectsEnabled\.value, errorStatus, projects\.value === null\)/)
  assert.match(helper, /if \(status === 404\) return 404/)
  assert.match(page, /responseStatus === 404 \? 'Not Found' : 'Bad Gateway'/)
})
