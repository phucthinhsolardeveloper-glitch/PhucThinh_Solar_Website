import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { isPageBeyondList, publicListErrorStatus } from '../shared/public-pagination.ts'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('unknown valid catalog category is a 404 from PHP and stays a 404 through Nitro', () => {
  const nitro = read('../server/routes/_api/catalog.get.ts')
  const page = read('../app/components/catalog/CatalogPage.vue')

  assert.match(nitro, /passThroughStatuses: \[404\]/)
  assert.match(page, /publicListErrorStatus\(catalogErrorStatus\)/)
})

test('refused filter values and pages past the end of a list are 404, a broken source stays 502', () => {
  assert.equal(publicListErrorStatus(400), 404)
  assert.equal(publicListErrorStatus(404), 404)
  assert.equal(publicListErrorStatus(500), 502)
  assert.equal(publicListErrorStatus(undefined), 502)
  assert.equal(isPageBeyondList(999, 372, 20), true)
  assert.equal(isPageBeyondList(19, 372, 20), false)
  assert.equal(isPageBeyondList(2, 0, 20), true)
  assert.equal(isPageBeyondList(1, 0, 20), false)
  for (const file of ['../app/components/catalog/CatalogPage.vue', '../app/components/catalog/BrandDetailPage.vue']) {
    assert.match(read(file), /if \(import\.meta\.server && (publicEnabled\.value && )?pageBeyondList\.value\) setResponseStatus\(404/, file)
  }
})
