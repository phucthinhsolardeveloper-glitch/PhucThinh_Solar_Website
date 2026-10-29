import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('search GET projection allowlists brand and public-price filters with parameterized product conditions', () => {
  const php = read('../../domains/phucthinhsolar.com/public_html/api/pts_v1_search.php')
  const nitro = read('../server/routes/_migration/search.get.ts')
  const contract = read('../shared/contracts/public-search.ts')

  assert.match(php, /\$allowed = array\('keyword','page','per_page','brand','price'\)/)
  assert.match(php, /array_fill\(0, count\(\$brandIds\), '\?'\)/)
  assert.match(php, /productPriceFilterSql\(implode\(',', \$priceKeys\)\)/)
  assert.match(php, /ptsCatalogBrandDto\(\$brandRow\)/)
  assert.match(php, /'filters'=>array\('brands'=>\$brands,'priceRanges'=>\$priceRanges\)/)
  assert.match(php, /'selected'=>array\('brandIds'=>\$brandIds,'priceKeys'=>\$priceKeys\)/)

  assert.match(nitro, /\['keyword', 'page', 'per_page', 'brand', 'price'\]/)
  assert.match(nitro, /query: \{ keyword, page, per_page: perPage, brand, price \}/)
  assert.match(contract, /priceRanges: Array<\{ key: string; label: string \}>/)
})

test('search filter controls send brand/price state through GET query and retain filters while paging', () => {
  const page = read('../app/components/catalog/SearchResultsPage.vue')
  const filters = read('../app/components/catalog/SearchFilterOptions.vue')

  assert.match(page, /route\.query\.brand/)
  assert.match(page, /route\.query\.price/)
  assert.match(page, /SearchFilterOptions/)
  assert.match(page, /brand: brand\.value/)
  assert.match(page, /price: price\.value/)
  assert.match(filters, /type="checkbox"/)
  assert.match(filters, /filters\.brands/)
  assert.match(filters, /filters\.priceRanges/)
})
