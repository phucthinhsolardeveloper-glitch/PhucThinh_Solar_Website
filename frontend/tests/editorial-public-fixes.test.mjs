import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import { formatProjectDate } from '../shared/project-date.ts'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('news category links use the canonical DTO URL publicly and retain query filtering in preview', () => {
  const filters = read('../app/components/news/NewsFilters.vue')

  assert.match(filters, /if \(!isMigrationPreview\.value\) return `\$\{category\.canonicalPath\}\$\{queryString\.value\}`/)
  assert.match(filters, /params\.set\('category', category\.slug\)/)
  assert.match(filters, /:href="categoryHref\(category\)"/)
})

test('project category links use the canonical DTO URL publicly and retain query filtering in preview', () => {
  const filters = read('../app/components/projects/ProjectFilters.vue')

  assert.match(filters, /\?category=\$\{encodeURIComponent\(category\.slug\)\}`\s*:\s*category\.url/)
  assert.match(filters, /:href="categoryHref\(category\)"/)
})

test('solution detail orders the hero before its switcher and only links to existing main content', () => {
  const page = read('../app/components/solution-detail/SolutionDetailPage.vue')
  const heroIndex = page.indexOf('<header class="mx-auto grid')
  const switcherIndex = page.indexOf('aria-label="Các giải pháp khác"')

  assert.ok(heroIndex >= 0 && switcherIndex > heroIndex)
  assert.match(page, /<UButton v-if="data\.solution\.content\.html"[^>]*href="#noi-dung-giai-phap"[^>]*>Tìm hiểu phương án<\/UButton>/)
})

test('project dates match legacy d/m/Y output and reject invalid calendar dates', () => {
  assert.equal(formatProjectDate('2026-03-09'), '09/03/2026')
  assert.equal(formatProjectDate('2026-02-30'), '2026-02-30')
  assert.equal(formatProjectDate(''), '')
  const page = read('../app/components/project-detail/ProjectDetailPage.vue')
  assert.match(page, /formatProjectDate\(project\.startDate\)/)
  assert.match(page, /formatProjectDate\(project\.endDate\)/)
})

test('policy detail shows the PHP-equivalent message when body content is empty', () => {
  const page = read('../app/pages/migration-policy-preview.vue')
  assert.match(page, /<section v-if="data\.content\.html"[\s\S]*?<\/section>\s*<p v-else[^>]*role="status">Nội dung đang cập nhật<\/p>/)
})
