import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isPublicSolutionDetailPayload } from '../shared/contracts/public-solution-detail.ts'
import { readFileSync } from 'node:fs'

const sanitized = { format: 'sanitized-html', html: '<p>Đoạn thật</p>', sanitizedBy: 'pts_public_richtext', sanitizerVersion: 1 }
const good = {
  schemaVersion: 1, locale: 'vi', path: '/giai-phap/dien-mat-troi/',
  solution: { id: 1, title: 'Điện mặt trời', slug: 'dien-mat-troi', url: '/giai-phap/dien-mat-troi/', category: 'Giải pháp doanh nghiệp', summary: '', image: '/upload/news/a.jpg', content: sanitized, extraContent: { ...sanitized, html: '' }, gallery: ['/upload/news/b.jpg'] },
  navigation: { allUrl: '/giai-phap/', items: [{ title: 'Điện mặt trời', slug: 'dien-mat-troi', url: '/giai-phap/dien-mat-troi/', image: '', active: true }] },
  equipment: [{ name: 'Biến tần', description: '', url: '/danh-muc-san-pham/bien-tan/', image: '' }],
  cta: { label: 'Nhận tư vấn giải pháp', href: '/lien-he/#yeu-cau-tu-van' },
  campaignCta: { title: 'Cần tư vấn?', description: 'Mô tả từ Admin', buttonLabel: 'Nhận tư vấn', image: '/assets/images/site-visuals/catalog-street-lighting.jpg', links: { consult: { label: 'Nhận tư vấn', href: '#ptsContactModal' } }, hotline: { label: 'Gọi ngay hotline', text: '0901 234 567', tel: '0901234567' } },
}
const solutionPage = readFileSync(new URL('../app/components/solution-detail/SolutionDetailPage.vue', import.meta.url), 'utf8')
const solutionHubPage = readFileSync(new URL('../app/pages/giai-phap.vue', import.meta.url), 'utf8')
const solutionDetailRoute = readFileSync(new URL('../app/components/views/SolutionDetailView.vue', import.meta.url), 'utf8')

test('accepts safe solution detail DTO', () => assert.equal(isPublicSolutionDetailPayload(good), true))
test('solution detail derives its TOC from sanitized content and reports that PHP omits it', () => {
  assert.match(solutionPage, /buildEditorialTocForFragments\([\s\S]*props\.data\.solution\.content, props\.data\.solution\.extraContent/)
})
test('accepts an empty Admin category and rejects unsafe category markup', () => {
  assert.equal(isPublicSolutionDetailPayload({ ...good, solution: { ...good.solution, category: '' } }), true)
  assert.equal(isPublicSolutionDetailPayload({ ...good, solution: { ...good.solution, category: '<script>bad</script>' } }), false)
})
test('accepts optional Admin SEO fields and rejects unsafe SEO media', () => {
  const seo = { title: 'SEO title', description: 'SEO description', keywords: 'solar', image: '/upload/news/og.jpg' }
  assert.equal(isPublicSolutionDetailPayload({ ...good, seo }), true)
  assert.equal(isPublicSolutionDetailPayload({ ...good, seo: { ...seo, image: '/upload/news/../private.jpg' } }), false)
  assert.equal(isPublicSolutionDetailPayload(good), true)
})
test('rejects executable or external media', () => assert.equal(isPublicSolutionDetailPayload({ ...good, solution: { ...good.solution, content: { ...sanitized, html: '<script>bad</script>' } } }), false))
test('rejects non-legacy CTA and invalid navigation URL', () => {
  assert.equal(isPublicSolutionDetailPayload({ ...good, cta: { ...good.cta, href: 'https://example.test' } }), false)
  assert.equal(isPublicSolutionDetailPayload({ ...good, navigation: { ...good.navigation, items: [{ ...good.navigation.items[0], url: '/giai-phap/../../bad/' }] } }), false)
})
test('accepts CMS campaign CTA and rejects unsafe CTA link or image', () => {
  assert.equal(isPublicSolutionDetailPayload({ ...good, campaignCta: null }), true)
  assert.equal(isPublicSolutionDetailPayload({ ...good, campaignCta: { ...good.campaignCta, links: { consult: { label: 'Tư vấn', href: 'https://evil.test' } } } }), false)
  assert.equal(isPublicSolutionDetailPayload({ ...good, campaignCta: { ...good.campaignCta, image: 'https://evil.test/a.jpg' } }), false)
})
test('rejects wrong sanitizer metadata on either content field', () => {
  assert.equal(isPublicSolutionDetailPayload({ ...good, solution: { ...good.solution, content: { ...sanitized, sanitizedBy: 'other' } } }), false)
  assert.equal(isPublicSolutionDetailPayload({ ...good, solution: { ...good.solution, extraContent: { ...sanitized, sanitizerVersion: 2 } } }), false)
})
test('solution detail renders its Admin controlled campaign CTA after related equipment', () => {
  assert.match(solutionPage, /<SolutionsCta v-if="data\.campaignCta" :cta="data\.campaignCta" :subject="`Giải pháp: \$\{data\.solution\.title\}`" \/>/)
  assert.match(solutionPage, /v-if="data\.solution\.category"[\s\S]*\{\{ data\.solution\.category \}\}/)
})
test('solution detail reads the legacy news SEO key and applies Admin metadata with content fallbacks', () => {
  assert.match(solutionDetailRoute, /data\.value\?\.seo\?\.title\s*\|\|\s*data\.value\?\.solution\.title/)
  assert.match(solutionDetailRoute, /data\.value\?\.seo\?\.description\s*\|\|\s*data\.value\?\.solution\.summary/)
  assert.match(solutionDetailRoute, /data\.value\?\.seo\?\.image\s*\|\|\s*data\.value\?\.solution\?\.image\s*\|\|\s*undefined/)
})
test('solution hub parent renders nested public detail page and scopes hub status and metadata to the hub path', () => {
  assert.match(solutionHubPage, /<NuxtPage v-if="!isHubRoute" \/>/)
  assert.match(solutionHubPage, /isHubRoute\.value && config\.public\.solutionsPublicRouteIndexable === true/)
  assert.match(solutionHubPage, /editorialPublicHubResponseStatus\(\s*isHubRoute\.value/)
  assert.doesNotMatch(solutionHubPage, /PublicRouteEnabled|isRuntimeFlagEnabled/)
})
