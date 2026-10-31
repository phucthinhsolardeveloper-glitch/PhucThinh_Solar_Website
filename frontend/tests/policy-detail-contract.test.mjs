import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { isPublicPolicyDetail } from '../shared/contracts/public-policy-detail.ts'

const good = {
  schemaVersion: 1,
  locale: 'vi',
  id: 4,
  title: 'Chính sách bảo hành',
  slug: 'chinh-sach-bao-hanh',
  canonical: '/ho-tro/chinh-sach-bao-hanh/',
  description: '',
  cover: '/upload/news/policy-cover.webp',
  date: '2026-10-01',
  author: { name: 'Phúc Thịnh Solar' },
  content: { format: 'sanitized-html', html: '<p>Nội dung an toàn</p>', sanitizedBy: 'pts_public_richtext', sanitizerVersion: 1 },
  related: [{ id: 5, title: 'Chính sách liên quan', slug: 'chinh-sach-lien-quan', canonical: '/chinh-sach-lien-quan/', date: '2026-09-20', description: '', image: '/upload/news/policy.webp' }],
  seo: { title: 'Chính sách bảo hành', description: '', keywords: '', canonical: '/ho-tro/chinh-sach-bao-hanh/' },
}
test('accepts published support policy DTO', () => assert.equal(isPublicPolicyDetail(good), true))
test('rejects wrong canonical and executable rich text', () => {
  assert.equal(isPublicPolicyDetail({ ...good, canonical: '/chinh-sach-bao-hanh/' }), false)
  assert.equal(isPublicPolicyDetail({ ...good, content: { ...good.content, html: '<script>alert(1)</script>' } }), false)
})
test('requires safe author and related policy entries', () => {
  assert.equal(isPublicPolicyDetail({ ...good, author: { name: '' } }), false)
  assert.equal(isPublicPolicyDetail({ ...good, related: [{ ...good.related[0], image: '/upload/news/../../private.jpg' }] }), false)
})
test('uses only the current policy cover for social metadata and validates its media path', () => {
  assert.equal(isPublicPolicyDetail({ ...good, cover: '' }), true)
  assert.equal(isPublicPolicyDetail({ ...good, cover: '/upload/news/../../private.jpg' }), false)
  assert.equal(isPublicPolicyDetail({ ...good, cover: '/upload/news/policy.svg' }), false)
  const page = readFileSync(new URL('../app/pages/migration-policy-preview.vue', import.meta.url), 'utf8')
  assert.match(page, /image:\s*computed\(\(\)\s*=>\s*data\.value\?\.cover\s*\|\|\s*undefined\)/)
  assert.doesNotMatch(page, /image:\s*computed\(\(\)\s*=>\s*data\.value\?\.related/)
})

test('policy metadata and related dates match the corresponding PHP formats', () => {
  const page = readFileSync(new URL('../app/pages/migration-policy-preview.vue', import.meta.url), 'utf8')
  assert.match(page, /function formatMetadataDate[\s\S]*new Intl\.DateTimeFormat\('en-US', \{ month: 'short', day: '2-digit', year: 'numeric', timeZone: 'UTC' \}\)/)
  assert.match(page, /function formatRelatedDate[\s\S]*\$\{match\[3\]\}\/\$\{match\[2\]\}\/\$\{match\[1\]\}/)
  assert.match(page, /formatMetadataDate\(data\.date\)/)
  assert.match(page, /formatRelatedDate\(item\.date\)/)
})

test('public policy JSON-LD uses only validated DTO fields and a canonical public URL', () => {
  const page = readFileSync(new URL('../app/pages/migration-policy-preview.vue', import.meta.url), 'utf8')
  assert.match(page, /canonicalPublicOrigin, canonicalPublicUrl/)
  assert.match(page, /const policyJsonLd = computed\(\(\) => \{[\s\S]*?if \(!isPublicRoute\.value \|\| !policy \|\| error\.value\) return null/)
  assert.match(page, /const url = canonicalPublicUrl\(siteOrigin, policy\.canonical\)/)
  assert.match(page, /'@type': 'NewsArticle'/)
  assert.match(page, /mainEntityOfPage: \{ '@type': 'WebPage', '@id': url \}/)
  assert.match(page, /author: \{ '@type': 'Person', name: policy\.author\.name \}/)
  assert.match(page, /node\.datePublished = policy\.date/)
  assert.match(page, /if \(image\) node\.image = \[image\]/)
  assert.match(page, /JSON\.stringify\(policyJsonLd\.value\)\.replace\(/)
  assert.doesNotMatch(page, /node\.dateModified\s*=|policy\.dateModified|new Date\(\).*dateModified/)
  assert.doesNotMatch(page, /'@type': 'NewsArticle'[\s\S]{0,100}description: ['"]/)
})

test('policy related cards use a keyboard-accessible responsive carousel', () => {
  const page = readFileSync(new URL('../app/pages/migration-policy-preview.vue', import.meta.url), 'utf8')
  assert.match(page, /aria-label="Tin trước"[^>]*@click="scrollRelated\(-1\)"/)
  assert.match(page, /aria-label="Tin tiếp theo"[^>]*@click="scrollRelated\(1\)"/)
  assert.match(page, /ref="relatedTrackRef" tabindex="0" class="policy-related-track[^\"]*overflow-x-auto/)
  assert.match(page, /function scrollRelated\(direction: -1 \| 1\)/)
  assert.match(page, /\.policy-related-arrow \{[\s\S]*?width: 44px;[\s\S]*?height: 44px;/)
  assert.match(page, /@media \(min-width: 455px\) \{ \.policy-related-card \{ flex-basis: calc\(50% - 12px\); \} \}/)
  assert.match(page, /@media \(min-width: 768px\) \{ \.policy-related-card \{ flex-basis: calc\(33\.333333% - 16px\); \} \}/)
  assert.match(page, /@media \(min-width: 1024px\) \{ \.policy-related-card \{ flex-basis: calc\(25% - 18px\); \} \}/)
  assert.match(page, /ref="relatedTrackRef" tabindex="0"/)
  assert.doesNotMatch(page, /item\.author/)

  const phpSlider = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/assets/wp-content/themes/local/js/main080f.js', import.meta.url), 'utf8')
  assert.match(phpSlider, /#CRsingle_list_related\.active_slider'\)\.slick\(\{[\s\S]*?slidesToShow: 4[\s\S]*?breakpoint: 1024,[\s\S]*?slidesToShow: 3[\s\S]*?breakpoint: 767,[\s\S]*?slidesToShow: 2[\s\S]*?breakpoint: 454,[\s\S]*?slidesToShow: 1/)
})
