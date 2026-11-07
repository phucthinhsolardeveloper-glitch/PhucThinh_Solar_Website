import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const read = (path) => readFileSync(new URL(path, new URL('../app/', import.meta.url)), 'utf8')
const composable = read('composables/usePublicSite.ts')
const app = read('app.vue')

test('shared site composable has one SSR async-data key and safe shell fallback', () => {
  assert.equal((composable.match(/useAsyncData/g) ?? []).length, 1)
  assert.match(composable, /PUBLIC_SITE_ASYNC_KEY = 'site'/)
  assert.match(composable, /server: true/)
  assert.match(composable, /publicSiteFallback/)
  assert.match(composable, /isPublicSitePayload\(data\.value\)/)
})

test('one shell renders header and footer on every page', () => {
  assert.match(app, /<SiteHeader :site="site" \/>/)
  assert.match(app, /<SiteFooter :site="site" \/>/)
  assert.doesNotMatch(app, /layout !== 'migration'|showShell/)
})
