import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import { publicPageQueryValue } from '../shared/public-pagination.ts'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('pagination prefers `page`, accepts the legacy PHP `p`, and rejects malformed values', () => {
  assert.equal(publicPageQueryValue('3', '2'), '3')
  assert.equal(publicPageQueryValue(undefined, '2'), '2')
  assert.equal(publicPageQueryValue('0', '4'), '4')
  for (const value of ['0', '-1', '01', '1.5', '10001', '999999999999999999999', ['2'], undefined]) {
    assert.equal(publicPageQueryValue(value, undefined), '')
  }
})

test('catalog, brand, and search routes map legacy PHP pagination into their API page query', () => {
  const catalog = read('../app/components/catalog/CatalogPage.vue')
  const brand = read('../app/components/catalog/BrandDetailPage.vue')
  const search = read('../app/components/catalog/SearchResultsPage.vue')
  for (const source of [catalog, brand, search]) {
    assert.match(source, /publicPageQueryValue\(route\.query\.page, route\.query\.p\)/)
  }
  assert.match(catalog, /catalogFilterKeys = new Set\(\[.*'p'/)
  assert.match(brand, /\['page', 'p', 'sort', 'price'\]/)
})
