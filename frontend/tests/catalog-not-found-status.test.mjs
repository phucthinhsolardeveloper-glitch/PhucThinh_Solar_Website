import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('unknown valid catalog category is a 404 from PHP and stays a 404 through Nitro', () => {
  const nitro = read('../server/routes/_api/catalog.get.ts')
  const page = read('../app/components/catalog/CatalogPage.vue')

  assert.match(nitro, /passThroughStatuses: \[404\]/)
  assert.match(page, /catalogErrorStatus === 404 \? 404 : 502/)
})
