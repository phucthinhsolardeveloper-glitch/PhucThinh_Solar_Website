import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('search GET projection allowlists brand and public-price filters with parameterized product conditions', () => {
  const nitro = read('../server/routes/_api/search.get.ts')
  const contract = read('../shared/contracts/public-search.ts')

  assert.match(nitro, /\['keyword', 'page', 'per_page', 'brand', 'price', 'sort'\]/)
  assert.match(nitro, /query: \{ keyword, page, per_page: perPage, brand, price, sort \}/)
  assert.match(contract, /priceRanges: Array<\{ key: string; label: string \}>/)
})

test('search filter controls send brand/price state through GET query and retain filters while paging', () => {
  const page = read('../app/components/catalog/SearchResultsPage.vue')
  const filters = read('../app/components/catalog/CatalogFilterSidebar.vue')

  assert.match(page, /route\.query\.brand/)
  assert.match(page, /route\.query\.price/)
  assert.match(page, /route\.query\.sort/)
  assert.match(page, /function onSortChange/)
  assert.match(page, /CatalogFilterSidebar/)
  assert.match(page, /brand: brand\.value/)
  assert.match(page, /price: price\.value/)
  assert.match(filters, /filters\.brands/)
  assert.match(filters, /filters\.priceRanges/)
})
