import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'
import { formatProjectDate } from '../shared/project-date.ts'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('news category links use the canonical DTO URL and keep the search text', () => {
  const filters = read('../app/components/news/NewsFilters.vue')

  assert.match(filters, /return `\$\{category\.canonicalPath\}\$\{queryString\.value\}`/)
  assert.match(filters, /:to="categoryHref\(category\)"/)
})

test('project category links use the canonical DTO URL', () => {
  const filters = read('../app/components/projects/ProjectFilters.vue')

  assert.match(filters, /return category\.url/)
  assert.match(filters, /:to="categoryHref\(category\)"/)
})

test('solution detail orders the hero before its switcher and only links to existing main content', () => {
  const page = read('../app/components/solution-detail/SolutionDetailPage.vue')
  const heroIndex = page.indexOf('<header ref="heroRef" :key="data.solution.slug" class="solution-hero"')
  const switcherIndex = page.indexOf('aria-label="Các giải pháp khác"')

  assert.ok(heroIndex >= 0 && switcherIndex > heroIndex)
  // Owner decision 2026-11-04: hero + pill rail redesign; the content link still targets the main content.
  assert.match(page, /<a v-if="hasContent" class="solution-hero__more" href="#noi-dung-giai-phap">Tìm hiểu phương án/)
  assert.match(page, /id="noi-dung-giai-phap"/)
  assert.match(page, /class="public-filter-chip[\s\S]*:aria-current="currentSlug === item\.slug \? 'page' : undefined"/)
  const categoryPage = read('../app/components/solutions/SolutionCategoryPage.vue')
  assert.match(categoryPage, /class="public-filter-chip flex min-h-11/)
  for (const file of ['../app/components/news/NewsFilters.vue', '../app/components/projects/ProjectFilters.vue']) {
    assert.match(read(file), /public-filter-chip/)
  }
  const publicStyles = read('../app/assets/css/main.css')
  assert.match(publicStyles, /\.public-filter-chip,\s*\.public-neutral-button/)
  assert.match(publicStyles, /\.public-filter-chip:not\(\[aria-current="page"\]\):hover/)
  assert.match(publicStyles, /\.public-filter-chip\[aria-current="page"\]/)
  assert.ok(publicStyles.indexOf('.public-filter-chip[aria-current="page"]') > publicStyles.indexOf('.public-neutral-button:hover'))
  const catalog = read('../app/components/catalog/CatalogPage.vue')
  assert.match(catalog, /border-transparent bg-gold-500 text-navy-950/)
  assert.match(catalog, /public-filter-chip public-navy-outline-button text-navy-800/)
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
  const page = read('../app/components/views/PolicyView.vue')
  assert.match(page, /<section v-if="data\.content\.html"[\s\S]*?<\/section>\s*<p v-else[^>]*role="status">Nội dung đang cập nhật<\/p>/)
})
