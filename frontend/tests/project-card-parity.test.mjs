import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('project cards use a flat surface and retain PHP project metadata', () => {
  const card = read('../app/components/projects/ProjectCard.vue')
  const php = read('../../domains/phucthinhsolar.com/public_html/templates/news/project_tpl.php')
  const phpCss = read('../../domains/phucthinhsolar.com/public_html/assets/css/project-hub.css')

  assert.match(card, /aspect-\[4\/3\]/)
  assert.doesNotMatch(card, /border border-slate|bg-white|hover:shadow/)
  assert.match(card, /project\.address/)
  assert.match(card, /project\.category\.name/)
  assert.match(php, /\$projectAddress/)
  assert.match(php, /\$projectCategoryName/)
  assert.match(phpCss, /\.pts-ph-card\{border:0!important;border-radius:4px!important;box-shadow:none!important;\}/)
})
