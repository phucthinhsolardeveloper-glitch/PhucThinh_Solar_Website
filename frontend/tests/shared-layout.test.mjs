import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync, readdirSync } from 'node:fs'

const root = new URL('../app/', import.meta.url)
const read = (path) => readFileSync(new URL(path, root), 'utf8')

const composable = read('composables/usePublicSite.ts')
const shell = read('components/layout/MigrationShell.vue')
const layout = read('layouts/migration.vue')
const about = read('pages/migration-about-preview.vue')
const previewPages = readdirSync(new URL('../app/pages/', import.meta.url))
  .filter((path) => path.startsWith('migration-') && path.endsWith('.vue'))
  .map((path) => ({ path, source: read(`pages/${path}`) }))

test('shared site composable has one SSR async-data key and safe shell fallback', () => {
  assert.equal((composable.match(/useAsyncData/g) ?? []).length, 1)
  assert.match(composable, /PUBLIC_SITE_ASYNC_KEY = 'migration-site'/)
  assert.match(composable, /server: true/)
  assert.match(composable, /publicSiteFallback/)
  assert.match(composable, /isPublicSitePayload\(data\.value\)/)
})

test('migration layout owns only global shell utilities and preview robots policy', () => {
  assert.match(layout, /<MigrationShell>/)
  assert.match(shell, /<SiteHeader v-if="shellOptions\.header" :site="site" \/>/)
  assert.match(shell, /<SiteFooter v-if="shellOptions\.footer" :site="site" \/>/)
  assert.doesNotMatch(shell, /SiteBreadcrumb|breadcrumbItems/)
  assert.match(shell, /route\.meta\.shell/)
  assert.match(shell, /header: options\.header !== false/)
  assert.match(shell, /footer: options\.footer !== false/)
  assert.match(shell, /noindex, nofollow, noarchive/)
  assert.match(shell, /role="status" aria-live="polite"/)
})

test('about pilot delegates global shell but owns its breadcrumb and CMS request', () => {
  assert.match(about, /layout: 'migration'/)
  assert.match(about, /<SiteBreadcrumb :items="\[\{ label: 'Giới thiệu' \}\]" \/>/)
  assert.doesNotMatch(about, /_api\/site|<SiteHeader|<SiteFooter/)
  assert.match(about, /_api\/about/)
  assert.match(about, /Đang tải dữ liệu CMS/)
  assert.match(about, /Preview không hiển thị dữ liệu giới thiệu giả/)
})

test('migration layout remains opt-in so independent preview pages keep their own shell', () => {
  const migrationLayoutUsers = previewPages
    .filter(({ source }) => /layout:\s*['"]migration['"]/.test(source))
    .map(({ path }) => path)
  assert.deepEqual(migrationLayoutUsers, ['migration-about-preview.vue'])
})

/**
 * layout-migration.css đã được chuyển sang Tailwind utility ngay trong
 * MigrationShell.vue (CODEX/Claude, Tailwind v4 full migration). Ý định gốc
 * của test này — shell chiếm đủ chiều cao viewport, vùng nội dung co giãn, và
 * KHÔNG được tự thêm trang trí (shadow/bo góc) — vẫn phải đúng, chỉ đổi chỗ
 * kiểm tra từ file CSS rời sang class Tailwind trên chính component.
 */
test('shared shell markup keeps content flexible without adding a decorative shell', () => {
  assert.match(shell, /class="flex min-h-screen flex-col/)
  assert.match(shell, /class="min-w-0 flex-1"/)
  assert.doesNotMatch(shell, /shadow-|rounded-(?!none)/)
})
