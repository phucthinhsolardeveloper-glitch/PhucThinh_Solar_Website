import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { isNewsDetailSlug, isPublicNewsDetailPayload } from '../shared/contracts/public-news-detail.ts'

const article = { schemaVersion: 1, locale: 'vi', id: 8, title: 'Bài viết CMS', slug: 'bai-viet-cms', canonical: '/bai-viet-cms/', date: '2026-10-26', datePublished: '2026-10-26', dateModified: '2026-10-27', author: { name: 'Phúc Thịnh Solar' }, description: 'Mô tả', cover: '/upload/news/article.webp', showCover: true, content: { format: 'sanitized-html', html: '<p>Nội dung</p>', sanitizedBy: 'pts_public_richtext', sanitizerVersion: 1 }, category: {}, media: { gallery: [{ image: '/upload/news/gallery.jpg', alt: '' }], videos: [{ url: 'https://www.youtube.com/watch?v=abc', title: '' }] }, seo: { title: 'Bài viết CMS | Phúc Thịnh Solar', description: 'Mô tả', keywords: '', canonical: '/bai-viet-cms' }, readOnly: true, legacyViewMutation: false }
const cta = { title: 'Cần tư vấn?', description: 'Liên hệ để được hỗ trợ.', buttonLabel: 'Nhận tư vấn', image: '', hotline: { label: 'Gọi ngay hotline', text: '0901 234 567', tel: '0901234567' } }
const good = { article, cta, related: [{ id: 9, title: 'Tin khác', slug: 'tin-khac', canonical: '/tin-khac/', date: '', author: 'Phúc Thịnh Solar', description: '', image: '' }] }
test('accepts safe read-only detail DTO', () => assert.equal(isPublicNewsDetailPayload(good), true))
test('requires PHP bare-slug canonical metadata while preserving the slash URL for links', () => {
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, seo: { ...article.seo, canonical: '/bai-viet-cms/' } } }), false)
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, canonical: '/bai-viet-cms' } }), false)
})
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
  assert.match(related, /size-11[\s\S]*size-11/)
  assert.match(related, /prefers-reduced-motion: reduce/)
  assert.match(related, /motion-reduce:scroll-auto/)
  assert.match(related, /year: 'numeric'/)
})

test('related-news cards use the approved flat article surface and readable copy sizes', () => {
  const related = readFileSync(new URL('../app/components/news-detail/NewsDetailRelated.vue', import.meta.url), 'utf8')
  const cardLink = related.match(/<a\s+class="([^"]+)"\s+:href="links\.news\(item\.slug\)"/)
  assert.ok(cardLink, 'related article remains a linked card')
  assert.doesNotMatch(cardLink[1], /rounded-xl|border|bg-white|shadow|hover:-translate/)
  assert.doesNotMatch(related, /Khám phá thêm|group-hover:scale-105/)
  assert.match(related, /<h3 class="[^\"]*text-base/)
  assert.match(related, /item\.description" class="[^\"]*text-base/)
  assert.match(related, /focus-visible:outline-2[\s\S]*:href="links\.news\(item\.slug\)"/)
  assert.match(related, /size-11[\s\S]*aria-label="Tin trước"[\s\S]*size-11[\s\S]*aria-label="Tin tiếp theo"/)
})

test('related news renders the same image, date, author, title, and description fields as PHP', async () => {
  const { readFileSync } = await import('node:fs')
  const phpTemplate = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/templates/news/news_detail_tpl.php', import.meta.url), 'utf8')
  const related = readFileSync(new URL('../app/components/news-detail/NewsDetailRelated.vue', import.meta.url), 'utf8')
  assert.match(phpTemplate, /id="CRsingle_blog_related"/)
  assert.match(phpTemplate, /getImage\(\['class' => 'lazy w-100','size-error' => '300x200x1'/)
  assert.match(phpTemplate, /date\('d\/m\/Y', \$v\['date_created'\]\)/)
  assert.match(phpTemplate, /newsAuthorName\(\$d, isset\(\$v\['id_user'\]\)/)
  assert.match(phpTemplate, /<h3><\?=\$v\['name'\.\$lang\]\?><\/h3>/)
  assert.match(phpTemplate, /<div class="des"><\?=\$v\['desc'\.\$lang\]\?><\/div>/)
  for (const field of ['item.image', 'item.date', 'item.author', 'item.title', 'item.description']) assert.ok(related.includes(field), `Nuxt related card renders ${field}`)
})

test('news detail share offers each social destination present in the PHP template', async () => {
  const { readFileSync } = await import('node:fs')
  const phpTemplate = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/templates/news/news_detail_tpl.php', import.meta.url), 'utf8')
  const share = readFileSync(new URL('../app/components/editorial/EditorialShare.vue', import.meta.url), 'utf8')
  for (const platform of ['twitter', 'facebook', 'linkedin', 'email', 'pinterest']) {
    assert.match(phpTemplate, new RegExp(`data-sharer="${platform}"`))
    assert.match(share, new RegExp(`type: '${platform === 'twitter' ? 'x' : platform}'`))
  }
  assert.match(share, /pinterest\.com\/pin\/create\/button\//)
  assert.match(share, /canonicalPath/)
})

test('news detail page renders CTA after related articles and uses PHP canonical/date values in JSON-LD', async () => {
  const { readFileSync } = await import('node:fs')
  const page = readFileSync(new URL('../app/pages/migration-news-detail-preview.vue', import.meta.url), 'utf8')
  const component = readFileSync(new URL('../app/components/news-detail/NewsDetailArticle.vue', import.meta.url), 'utf8')
  assert.match(page, /<NewsDetailRelated[^>]+\/>\s*<NewsCta v-if="payload\.cta"/)
  assert.match(page, /canonicalPublicOrigin\(config\.public\.canonicalSiteOrigin\)/)
  assert.match(page, /canonicalPublicUrl\(siteOrigin, article\.seo\.canonical\)/)
  assert.match(page, /canonicalPath: computed\(\(\) => payload\.value\?\.article\.seo\.canonical/)
  assert.match(component, /:canonical-path="article\.seo\.canonical"/)
  assert.match(page, /publisher: \{ '@type': 'Organization', '@id': `\$\{siteOrigin\}\/\#organization`/)
  assert.match(page, /article\.datePublished/)
  assert.match(page, /article\.dateModified/)
  assert.match(page, /payload \? \[\{ label: payload\.article\.title \}\]/)
  assert.match(component, /Nội dung đang cập nhật/)
  assert.match(component, /formatDate\(article\.date\)/)
})
