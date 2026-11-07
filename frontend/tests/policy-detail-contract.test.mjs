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
  const page = readFileSync(new URL('../app/components/views/PolicyView.vue', import.meta.url), 'utf8')
  assert.match(page, /image:\s*computed\(\(\)\s*=>\s*data\.value\?\.cover\s*\|\|\s*undefined\)/)
  assert.doesNotMatch(page, /image:\s*computed\(\(\)\s*=>\s*data\.value\?\.related/)
})

test('policy metadata date renders in Vietnamese day/month/year', () => {
  const page = readFileSync(new URL('../app/components/views/PolicyView.vue', import.meta.url), 'utf8')
  assert.match(page, /function formatMetadataDate[\s\S]*new Intl\.DateTimeFormat\('vi-VN'/)
  assert.match(page, /formatMetadataDate\(data\.date\)/)
})

test('public policy JSON-LD uses only validated DTO fields and a canonical public URL', () => {
  const page = readFileSync(new URL('../app/components/views/PolicyView.vue', import.meta.url), 'utf8')
  assert.match(page, /canonicalPublicOrigin, canonicalPublicUrl/)
  assert.match(page, /const policyJsonLd = computed\(\(\) => \{[\s\S]*?if \(!policy \|\| error\.value\) return null/)
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

// Owner decision 2026-11-03: the related-news carousel is replaced by a policy rail in the hero
// that links every policy (current one marked with aria-current).
test('policy pages link all policies through an accessible rail', () => {
  const page = readFileSync(new URL('../app/components/views/PolicyView.vue', import.meta.url), 'utf8')
  assert.match(page, /<nav v-if="policies\.length"[^>]*class="policy-rail" aria-label="Chính sách và hỗ trợ">/)
  assert.match(page, /:aria-current="isCurrentPolicy\(pol\.href\) \? 'page' : undefined"/)
  assert.match(page, /:to="links\.generic\(pol\.href\)"/)
  assert.doesNotMatch(page, /item\.author/)
})
