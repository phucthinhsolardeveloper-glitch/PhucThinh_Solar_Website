import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { isNewsDetailSlug, isPublicNewsDetailPayload } from '../shared/contracts/public-news-detail.ts'

const article = { schemaVersion: 1, locale: 'vi', id: 8, title: 'Bài viết CMS', slug: 'bai-viet-cms', canonical: '/bai-viet-cms/', date: '2026-10-26', datePublished: '2026-10-26', dateModified: '2026-10-27', author: { name: 'Phúc Thịnh Solar' }, description: 'Mô tả', cover: '/upload/news/article.webp', showCover: true, content: { format: 'sanitized-html', html: '<p>Nội dung</p>', sanitizedBy: 'pts_public_richtext', sanitizerVersion: 1 }, category: {}, media: { gallery: [{ image: '/upload/news/gallery.jpg', alt: '' }], videos: [{ url: 'https://www.youtube.com/watch?v=abc', title: '' }] }, seo: { title: 'Bài viết CMS', description: 'Mô tả', keywords: '', canonical: '/bai-viet-cms/' }, readOnly: true, legacyViewMutation: false }
const cta = { title: 'Cần tư vấn?', description: 'Liên hệ để được hỗ trợ.', buttonLabel: 'Nhận tư vấn', image: '', hotline: { label: 'Gọi ngay hotline', text: '0901 234 567', tel: '0901234567' } }
const good = { article, cta, related: [{ id: 9, title: 'Tin khác', slug: 'tin-khac', canonical: '/tin-khac/', date: '', author: 'Phúc Thịnh Solar', description: '', image: '' }] }
test('accepts safe read-only detail DTO', () => assert.equal(isPublicNewsDetailPayload(good), true))
test('emits Admin SEO keywords on the public bare-slug page', () => {
  const page = readFileSync(new URL('../app/pages/migration-news-detail-preview.vue', import.meta.url), 'utf8')
  assert.match(page, /payload\.value\?\.article\.seo\.keywords/)
  assert.match(page, /useHead\(\{ meta: computed\(\(\) => pageKeywords\.value \? \[\{ name: 'keywords', content: pageKeywords\.value \}\] : \[\]\) \}\)/)
})
test('accepts only sanitized optional legacy quote content', () => {
  const quote = { format: 'sanitized-html', html: '<p>Thông tin bổ sung</p>', sanitizedBy: 'pts_public_richtext', sanitizerVersion: 1 }
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, quote } }), true)
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, quote: null } }), true)
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, quote: { ...quote, html: '<script>x</script>' } } }), false)
})
test('rejects unsafe slugs, media and raw HTML', () => {
  assert.equal(isNewsDetailSlug('../secret'), false)
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, title: '<script>x</script>' } }), false)
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, cover: '/upload/news/../../secret.jpg' } }), false)
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, media: { ...article.media, videos: [{ url: 'javascript:alert(1)', title: '' }] } } }), false)
})
test('requires no legacy view mutation and rejects wrong content contract', () => {
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, legacyViewMutation: true } }), false)
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, content: { format: 'plain-text', value: 'x', richTextDeferred: true } } }), false)
})
test('rejects content that carries unsanitized markup or wrong sanitizer metadata', () => {
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, content: { ...article.content, html: '<script>alert(1)</script>' } } }), false)
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, content: { ...article.content, sanitizedBy: 'something-else' } } }), false)
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, content: { ...article.content, sanitizerVersion: 2 } } }), false)
})
test('validates PHP-owned article dates, cover display flag, and optional Admin CTA', () => {
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, dateModified: '2026-02-31' } }), false)
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, showCover: 'yes' } }), false)
  assert.equal(isPublicNewsDetailPayload({ ...good, cta: null }), true)
  assert.equal(isPublicNewsDetailPayload({ ...good, cta: { ...cta, image: '/upload/news/../bad.jpg' } }), false)
})

test('requires the PHP related-news author metadata', () => {
  assert.equal(isPublicNewsDetailPayload({ ...good, related: [{ ...good.related[0], author: '' }] }), false)
  assert.equal(isPublicNewsDetailPayload({ ...good, related: [{ id: 9, title: 'Tin khác', slug: 'tin-khac', canonical: '/tin-khac/', date: '', description: '', image: '' }] }), false)
})

test('related-news rail keeps the PHP author query, four-card desktop page, and working scroll controls', async () => {
  const { readFileSync } = await import('node:fs')
  const phpApi = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/api/pts_v1_news_detail.php', import.meta.url), 'utf8')
  const phpDto = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/libraries/pts_public_news_detail_api.php', import.meta.url), 'utf8')
  const related = readFileSync(new URL('../app/components/news-detail/NewsDetailRelated.vue', import.meta.url), 'utf8')
  assert.match(phpApi, /left join #_user u on u\.id=n\.id_user/)
  assert.match(phpDto, /'author'\s*=>\s*ptsNewsDetailAuthor\(/)
  assert.match(related, /nav:w-\[calc\(\(100%_-_4\.5rem\)\/4\)\]/)
  assert.match(related, /tb:w-\[calc\(\(100%_-_1\.5rem\)\/2\)\]/)
  assert.match(related, /scrollBy\(\{ left: direction \* element\.clientWidth/)
  assert.match(related, /year: 'numeric'/)
})

test('news detail page renders CTA after related articles and uses PHP canonical/date values in JSON-LD', async () => {
  const { readFileSync } = await import('node:fs')
  const page = readFileSync(new URL('../app/pages/migration-news-detail-preview.vue', import.meta.url), 'utf8')
  const component = readFileSync(new URL('../app/components/news-detail/NewsDetailArticle.vue', import.meta.url), 'utf8')
  assert.match(page, /<NewsDetailRelated[^>]+\/>\s*<NewsCta v-if="payload\.cta"/)
  assert.match(page, /canonicalPublicOrigin\(config\.public\.canonicalSiteOrigin\)/)
  assert.match(page, /canonicalPublicUrl\(siteOrigin, article\.canonical\)/)
  assert.match(page, /publisher: \{ '@type': 'Organization', '@id': `\$\{siteOrigin\}\/\#organization`/)
  assert.match(page, /article\.datePublished/)
  assert.match(page, /article\.dateModified/)
  assert.match(page, /payload \? \[\{ label: payload\.article\.title \}\]/)
  assert.match(component, /Nội dung đang cập nhật/)
  assert.match(component, /formatDate\(article\.date\)/)
})
