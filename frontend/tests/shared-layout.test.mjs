import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'

const root = new URL('../app/', import.meta.url)
const read = (path) => readFileSync(new URL(path, root), 'utf8')

const composable = read('composables/usePublicSite.ts')
const shell = read('components/layout/MigrationShell.vue')
const layout = read('layouts/migration.vue')
const about = read('pages/migration-about-preview.vue')
const css = read('assets/css/layout-migration.css')

test('shared site composable has one SSR async-data key and safe shell fallback', () => {
  assert.equal((composable.match(/useAsyncData/g) ?? []).length, 1)
  assert.match(composable, /PUBLIC_SITE_ASYNC_KEY = 'migration-site'/)
  assert.match(composable, /server: true/)
  assert.match(composable, /publicSiteFallback/)
  assert.match(composable, /isPublicSitePayload\(data\.value\)/)
})

test('migration layout owns shell, breadcrumb and preview robots policy', () => {
  assert.match(layout, /<MigrationShell>/)
  assert.match(shell, /<SiteHeader :site="site" \/>/)
  assert.match(shell, /<SiteFooter :site="site" \/>/)
  assert.match(shell, /<SiteBreadcrumb :items="breadcrumbItems" \/>/)
  assert.match(shell, /noindex, nofollow, noarchive/)
  assert.match(shell, /role="status" aria-live="polite"/)
})

test('about pilot delegates the shell and keeps only its own CMS request', () => {
  assert.match(about, /layout: 'migration'/)
  assert.match(about, /breadcrumb: \[\{ label: 'Giới thiệu' \}\]/)
  assert.doesNotMatch(about, /_migration\/site|<SiteHeader|<SiteFooter|<SiteBreadcrumb/)
  assert.match(about, /_migration\/about/)
  assert.match(about, /Đang tải dữ liệu CMS/)
  assert.match(about, /Preview không hiển thị dữ liệu giới thiệu giả/)
})

test('shared shell CSS keeps content flexible without adding a decorative shell', () => {
  assert.match(css, /min-height: 100vh/)
  assert.match(css, /flex: 1 0 auto/)
  assert.doesNotMatch(css, /box-shadow|border-radius/)
})
