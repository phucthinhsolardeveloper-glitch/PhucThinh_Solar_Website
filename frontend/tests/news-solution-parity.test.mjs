import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('news detail keeps its summary for metadata and renders the PHP article body only once', () => {
  const article = read('../app/components/news-detail/NewsDetailArticle.vue')
  const phpTemplate = read('../../domains/phucthinhsolar.com/public_html/templates/news/news_detail_tpl.php')

  assert.doesNotMatch(article, /article\.description/)
  assert.match(article, /<EditorialRichText v-if="article\.content\.html"/)
  assert.match(phpTemplate, /class="content-main w-clear" id="toc-content"><\?=\$newsDetailContent\?>/)
  assert.doesNotMatch(phpTemplate, /\$rowDetail\['desc/)
})

test('solution detail uses the PHP editorial style and equipment heading', () => {
  const page = read('../app/components/solution-detail/SolutionDetailPage.vue')
  const phpTemplate = read('../../domains/phucthinhsolar.com/public_html/templates/news/service_detail_tpl.php')
  const phpCss = read('../../domains/phucthinhsolar.com/public_html/assets/css/flat-surface-system.css')

  assert.equal((page.match(/<EditorialRichText[^>]+variant="solution"/g) || []).length, 2)
  assert.match(phpTemplate, /class="pts-solution-editorial content-main w-clear"/)
  assert.match(page, /Thiết bị cho một hệ thống hoàn chỉnh/)
  assert.match(phpTemplate, /Thiết bị cho một hệ thống hoàn chỉnh/)
  assert.match(phpTemplate, /class="pts-sh-equipment-link"/)
  assert.match(page, /grid grid-cols-1 gap-2 min-\[521px\]:grid-cols-2 min-\[1101px\]:grid-cols-4/)
  assert.match(page, /flex min-w-0 items-center gap-3 px-3\.5 py-2\.5/)
  assert.doesNotMatch(page, /rounded-chip border border-slate-200\/90 bg-white p-4/)
  assert.match(phpCss, /\.pts-sh-equipment-link,[\s\S]*?border:0!important;[\s\S]*?background:transparent!important/)
})
