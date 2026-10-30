import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import test from 'node:test'

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8')

test('TOC scrolling honors reduced motion, transfers keyboard focus, and resumes on keyboard scroll', () => {
  const source = read('../app/composables/useTocScrollspy.ts')
  assert.match(source, /matchMedia\('\(prefers-reduced-motion: reduce\)'\)/)
  assert.match(source, /target\.focus\(\{ preventScroll: true \}\)/)
  assert.match(source, /window\.addEventListener\('keydown', onKeyboardScroll\)/)
  assert.match(source, /window\.removeEventListener\('keydown', onKeyboardScroll\)/)
  assert.doesNotMatch(source, /behavior:\s*'smooth'/)
})

test('News and policy TOCs expose active state and use the shared scrollspy', () => {
  const news = read('../app/components/news-detail/NewsDetailArticle.vue')
  const policy = read('../app/pages/migration-policy-preview.vue')
  for (const source of [news, policy]) {
    assert.match(source, /useTocScrollspy\(/)
    assert.match(source, /data-toc-id="item\.id"/)
    assert.match(source, /aria-current="activeHeadingId === item\.id \? 'true' : undefined"/)
    assert.match(source, /@click\.prevent="scrollToHeading\(item\.id\)"/)
  }
})
