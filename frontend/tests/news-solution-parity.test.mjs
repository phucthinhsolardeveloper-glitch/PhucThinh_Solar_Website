import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('news detail keeps its summary for metadata and renders sanitized editorial content once', () => {
  const article = read('../app/components/news-detail/NewsDetailArticle.vue')

  assert.doesNotMatch(article, /article\.description/)
  assert.match(article, /<div v-if="article\.content\.html" id="news-article-content">\s*<EditorialRichText :content="editorial\.content" variant="news" \/>\s*<\/div>/)
  assert.match(article, /<EditorialRichText :content="article\.quote" variant="news" \/>/)
  assert.equal((article.match(/<EditorialRichText/g) || []).length, 2)
})

test('solution detail uses the PHP editorial style and equipment heading', () => {
  const page = read('../app/components/solution-detail/SolutionDetailPage.vue')

  assert.match(page, /<SolutionsStory v-if="hasContent"/)
  assert.match(page, /Thiết bị cho một hệ thống hoàn chỉnh/)
  assert.match(page, /grid grid-cols-1 gap-2 min-\[521px\]:grid-cols-2 min-\[1101px\]:grid-cols-4/)
  assert.match(page, /flex min-w-0 items-center gap-3 px-3\.5 py-2\.5/)
  assert.doesNotMatch(page, /rounded-chip border border-slate-200\/90 bg-white p-4/)
})
