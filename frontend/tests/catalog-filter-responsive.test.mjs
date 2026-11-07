import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('long catalog tree stays collapsed by default on mobile but remains open at desktop breakpoint', () => {
  const sidebar = read('../app/components/catalog/CatalogFilterSidebar.vue')

  assert.match(sidebar, /const categoriesExpanded = ref\(false\)/)
  assert.doesNotMatch(sidebar, /watch\(\(\) => props\.selectedCategory/)
  assert.match(sidebar, /class="m-0 list-none p-0 nav:block nav:pt-0"/)
  assert.match(sidebar, /:class="categoriesExpanded \? 'block pt-3' : 'hidden'"/)
  assert.match(sidebar, /aria-expanded="categoriesExpanded"/)
  assert.match(sidebar, /categoriesExpanded \? 'Ẩn danh mục' : \(selectedCategory \? 'Mở danh mục khác' : 'Chọn danh mục'\)/)
})

test('catalog query filters expose their active state to assistive technology', () => {
  const sidebar = read('../app/components/catalog/CatalogFilterSidebar.vue')
  assert.match(sidebar, /selected\.subcategoryIds\.includes\(item\.id\) \? 'true' : undefined/)
  assert.match(sidebar, /selected\.brandIds\.includes\(brand\.id\) \? 'true' : undefined/)
  assert.match(sidebar, /selected\.priceKeys\.includes\(range\.key\) \? 'true' : undefined/)
  assert.match(sidebar, /\(selected\.needKeys \?\? \[\]\)\.includes\(item\.key\) \? 'true' : undefined/)
})

test('desktop catalog filters use a light gray surface while the mobile drawer stays white', () => {
  const sidebar = read('../app/components/catalog/CatalogFilterSidebar.vue')
  assert.match(sidebar, /drawer \? 'bg-white px-4 pb-6 pt-4' : 'rounded-md bg-slate-50 p-4'/)
})
