import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import path from 'node:path'
import { test } from 'node:test'

const root = path.resolve(import.meta.dirname, '..')
const source = (route) => readFileSync(path.join(root, route), 'utf8')

test('mutable uploads and thumbnails require revalidation', () => {
  for (const route of [
    'server/routes/upload/[...path].get.ts',
    'server/routes/thumbs/[...path].get.ts',
  ]) {
    assert.match(source(route), /Cache-Control', 'private, no-cache, must-revalidate'/, route)
  }
})

test('product documents are not stored while static assets keep their short cache', () => {
  assert.match(
    source('server/routes/_api/product-document.get.ts'),
    /Cache-Control', 'private, no-store'/,
  )
  assert.match(
    source('server/routes/assets/[...path].get.ts'),
    /Cache-Control', 'private, max-age=300'/,
  )
})
