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

test('flag-off product alias returns the original PHP route for its published-state and redirect rules', () => {
  assert.deepEqual(publicProductAliasDisposition('published-product', false, null, undefined), {
    mode: 'legacy', path: '/product/published-product',
  })
})

test('flag-on alias redirects only a PHP-confirmed published product to its canonical PDP', () => {
  assert.deepEqual(publicProductAliasDisposition('published-product', true, fixture, undefined), {
    mode: 'canonical', path: '/san-pham/published-product', statusCode: 301,
  })
  assert.deepEqual(publicProductAliasDisposition('news-slug', true, {
    schemaVersion: 1, slug: 'news-slug', kind: 'news', canonicalPath: '/news-slug/',
  }, undefined), { mode: 'error', statusCode: 404 })
})

test('alias resolution preserves source 404 and fails closed on upstream or invalid responses', () => {
  assert.deepEqual(publicProductAliasDisposition('published-product', true, null, 404), { mode: 'error', statusCode: 404 })
  assert.deepEqual(publicProductAliasDisposition('published-product', true, null, 502), { mode: 'error', statusCode: 502 })
  assert.deepEqual(publicProductAliasDisposition('published-product', true, null, undefined), { mode: 'error', statusCode: 502 })
  assert.deepEqual(publicProductAliasDisposition('other-product', true, fixture, undefined), { mode: 'error', statusCode: 502 })
  assert.deepEqual(publicProductAliasDisposition('../private', false, null, undefined), { mode: 'error', statusCode: 404 })
})
