import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import path from 'node:path'
import { test } from 'node:test'

const root = path.resolve(import.meta.dirname, '..')
const source = (route) => readFileSync(path.join(root, route), 'utf8')

test('uploads and thumbnails pass conditional requests through so Laravel can answer 304', () => {
  assert.match(source('server/utils/media-proxy.ts'), /'range', 'if-none-match', 'if-modified-since'/)
  for (const route of ['server/routes/upload/[...path].get.ts', 'server/routes/thumbs/[...path].get.ts']) {
    assert.match(source(route), /proxyBackendMedia\(event, /, route)
  }
})

test('product documents are not stored', () => {
  assert.match(
    source('server/routes/_api/product-document.get.ts'),
    /Cache-Control', 'private, no-store'/,
  )
})
