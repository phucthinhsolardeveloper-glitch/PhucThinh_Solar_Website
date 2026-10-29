import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('unknown valid catalog category is a 404 from PHP and stays a 404 through Nitro', () => {
  const php = read('../../domains/phucthinhsolar.com/public_html/api/pts_v1_catalog.php')
  const nitro = read('../server/routes/_migration/catalog.get.ts')
  const page = read('../app/components/catalog/CatalogPage.vue')

  assert.match(php, /if\(\$segments && empty\(\$resolved\)\) ptsCatalogError\(404, 'CATEGORY_NOT_FOUND'\)/)
  assert.match(php, /if\(\$category === null\) ptsCatalogError\(404, 'CATEGORY_NOT_FOUND'\)/)
  assert.match(nitro, /passThroughStatuses: \[404\]/)
  assert.match(page, /catalogErrorStatus === 404 \? 404 : 502/)
})
