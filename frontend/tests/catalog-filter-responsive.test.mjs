import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('long catalog tree stays collapsed by default on mobile but remains open at desktop breakpoint', () => {
  const sidebar = read('../app/components/catalog/CatalogFilterSidebar.vue')

  assert.match(sidebar, /const categoriesExpanded = ref\(false\)/)
  assert.doesNotMatch(sidebar, /watch\(\(\) => props\.selectedCategory/)
  assert.match(sidebar, /class="m-0 list-none p-0 pt-3 nav:block nav:pt-0"/)
  assert.match(sidebar, /:class="categoriesExpanded \? 'block' : 'hidden'"/)
  assert.match(sidebar, /aria-expanded="categoriesExpanded"/)
  assert.match(sidebar, /Mở danh sách danh mục/)
})
