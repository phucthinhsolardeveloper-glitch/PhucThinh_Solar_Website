import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import { publicProductAliasDisposition } from '../shared/public-product-alias-route.ts'

const fixture = {
  schemaVersion: 1,
  slug: 'published-product',
  kind: 'pdp',
  canonicalPath: '/san-pham/published-product',
}

test('product alias resolves only a PHP-confirmed published product to its canonical PDP', () => {
  assert.deepEqual(publicProductAliasDisposition('published-product', fixture, undefined), {
    mode: 'canonical', path: '/san-pham/published-product', statusCode: 301,
  })
  assert.deepEqual(publicProductAliasDisposition('news-slug', {
    schemaVersion: 1, slug: 'news-slug', kind: 'news', canonicalPath: '/news-slug/',
  }, undefined), { mode: 'error', statusCode: 404 })
})

test('alias resolution preserves source 404 and fails closed on upstream or invalid responses', () => {
  assert.deepEqual(publicProductAliasDisposition('published-product', null, 404), { mode: 'error', statusCode: 404 })
  assert.deepEqual(publicProductAliasDisposition('published-product', null, 502), { mode: 'error', statusCode: 502 })
  assert.deepEqual(publicProductAliasDisposition('published-product', null, undefined), { mode: 'error', statusCode: 502 })
  assert.deepEqual(publicProductAliasDisposition('other-product', fixture, undefined), { mode: 'error', statusCode: 502 })
  assert.deepEqual(publicProductAliasDisposition('../private', null, undefined), { mode: 'error', statusCode: 404 })
})
