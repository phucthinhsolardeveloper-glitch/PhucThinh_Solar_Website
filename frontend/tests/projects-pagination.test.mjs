import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('projects pagination follows PHP `p` URLs and maps them to the DTO `page` query', () => {
  const php = read('../../domains/phucthinhsolar.com/public_html/sources/project_hub.php')
  const api = read('../../domains/phucthinhsolar.com/public_html/api/pts_v1_projects.php')
  const page = read('../app/components/views/ProjectsView.vue')

  assert.match(php, /\$_GET\['p'\]/)
  assert.match(api, /\$_GET\['page'\]/)
  assert.match(page, /const value = route\.query\.p/)
  assert.match(page, /page: String\(page\.value\)/)
  assert.match(page, /_api\/projects', \{ query: requestQuery\.value \}/)
})

test('projects pagination keeps the selected category, route family, and noindex query behavior', () => {
  const page = read('../app/components/views/ProjectsView.vue')

  assert.match(page, /path: PROJECTS_PUBLIC_PATH,/)
  assert.match(page, /category: category\.value/)
  assert.match(page, /p: String\(nextPage\)/)
  assert.match(page, /'category', 'p', 'page'/)
  assert.match(page, /aria-label="Phân trang dự án"/)
  assert.match(page, /aria-label="Trang trước"/)
  assert.match(page, /SitePaginationButton[\s\S]*?active[\s\S]*?aria-label="`Trang \$\{item\.page\}`"/)
  assert.match(page, /aria-label="Trang tiếp theo"/)
  assert.match(page, /:to="pageLink\(item\.page\)"/)
})
