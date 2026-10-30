import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('nullable solutions intro fallback does not reserve a viewport-sized blank area', () => {
  const page = read('../app/pages/giai-phap.vue')
  assert.match(page, /<h1 v-else class="sr-only">\{\{ data\.title \}\}<\/h1>/)
  assert.doesNotMatch(page, /<h1 v-else class="[^\"]*min-h-\[45vh\]/)
})

test('news search uses the mobile body text size', () => {
  const page = read('../app/pages/migration-news-preview.vue')
  assert.match(page, /id="news-search"[^>]*class="[^\"]*text-base/)
  assert.doesNotMatch(page, /id="news-search"[^>]*text-\[13px\]/)
})

test('projects empty state takes only the space its message needs', () => {
  const page = read('../app/pages/migration-projects-preview.vue')
  assert.match(page, /v-if="!projects\.featured && !projects\.projects\.length" class="py-6 text-slate-500"/)
  assert.doesNotMatch(page, /v-if="!projects\.featured && !projects\.projects\.length" class="[^\"]*min-h-/)
})

test('news TOC links meet the minimum touch target in both dimensions', () => {
  const article = read('../app/components/news-detail/NewsDetailArticle.vue')
  assert.match(article, /class="inline-flex min-h-11 min-w-11 items-center px-2[^\"]*"/)
  assert.match(article, /:href="`#\$\{item\.id\}`"[\s\S]*?@click\.prevent="scrollToHeading\(item\.id\)"/)
})

test('About quick links meet the touch target and intro copy stays at least 16px on mobile', () => {
  const page = read('../app/components/about/AboutPreviewPage.vue')
  const quickNav = page.split('<nav class="bg-white"')[1]?.split('</nav>')[0] ?? ''
  assert.equal((quickNav.match(/inline-flex min-h-11 min-w-11/g) ?? []).length, 5)
  assert.match(page, /max-\[800px\]:text-base/)
  assert.doesNotMatch(page, /max-\[800px\]:text-\[15\.5px\]/)
})
