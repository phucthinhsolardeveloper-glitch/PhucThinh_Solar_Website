import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { isProjectSlug, isPublicProjectDetailPayload } from '../shared/contracts/public-project-detail.ts'
const projectDetailPage = readFileSync(new URL('../app/components/project-detail/ProjectDetailPage.vue', import.meta.url), 'utf8')
const good = { schemaVersion: 1, locale: 'vi', project: { id: 7, title: 'Dự án thật', slug: 'du-an-that', url: '/du-an-that/', summary: '', coverImage: '/upload/news/cover.jpg', category: { id: 3, name: 'Hộ gia đình', slug: 'ho-gia-dinh', url: '/danh-muc-du-an/ho-gia-dinh/' }, client: 'CMS', address: '', startDate: '', endDate: '', gallery: [{ type: 'image', src: '/upload/news/gallery.webp', caption: 'Ảnh thật' }], relatedEquipment: [{ id: 9, name: 'Thiết bị thật', slug: 'thiet-bi-that', url: '/san-pham/thiet-bi-that', code: '', image: '/upload/product/device.jpg' }], relatedProjects: [], cta: null }, content: { format: 'sanitized-html', html: '<p>Nội dung</p>', sanitizedBy: 'pts_public_richtext', sanitizerVersion: 1 }, seo: { title: 'Dự án thật | Phúc Thịnh Solar', description: 'Mô tả SEO từ Admin', keywords: 'dự án, điện mặt trời' }, canonical: { url: '/du-an-that/', sources: { project: 'legacy /du-an-that/' } }, parity: { viewCount: { included: false, reason: 'read_only_preview_does_not_increment_legacy_view_count' } } }
test('validates real project detail contract', () => assert.equal(isPublicProjectDetailPayload(good), true))
test('project detail SEO metadata comes from PHP projection and exposes an HTML description meta', () => {
  const endpoint = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/api/pts_v1_project_detail.php', import.meta.url), 'utf8')
  const dto = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/libraries/pts_public_project_detail_api.php', import.meta.url), 'utf8')
  const page = readFileSync(new URL('../app/pages/migration-project-detail-preview.vue', import.meta.url), 'utf8')
  assert.match(endpoint, /select titlevi,descriptionvi,keywordsvi from #_seo where id_parent = \? and com = \? and act = \? and type = \? limit 0,1/)
  assert.match(endpoint, /'news', 'man', 'du-an'/)
  assert.match(dto, /'seo' => ptsProjectDetailSeoDto\(\$title, \$seoRow\)/)
  assert.match(page, /description:\s*computed\(\(\) => payload\.value\?\.seo\.description \?\? ''\)/)
  assert.match(page, /title: computed\(\(\) => payload\.value \? \(isBarePublicRoute\.value \? payload\.value\.seo\.title/)
  assert.match(page, /payload\.value\?\.seo\.keywords/)
  assert.match(page, /name: 'keywords', content: pageKeywords\.value/)
  assert.equal(isPublicProjectDetailPayload({ ...good, seo: { ...good.seo, description: '' } }), true)
  assert.equal(isPublicProjectDetailPayload({ ...good, seo: { ...good.seo, title: '' } }), false)
})
test('project facts use flat definition-list metadata instead of decorative pills', () => {
  assert.match(projectDetailPage, /<dl class="mt-6 flex flex-wrap/)
  assert.match(projectDetailPage, /<dt class="text-xs font-bold uppercase/)
  assert.doesNotMatch(projectDetailPage, /rounded-full bg-white px-4 py-2\.5/)
  assert.match(projectDetailPage, /nav:grid-cols-\[0\.86fr_1\.14fr\]/)
  assert.doesNotMatch(projectDetailPage, /project\.summary/)
  assert.match(projectDetailPage, /<section aria-labelledby="project-summary-title"[\s\S]*?<EditorialRichText :content="content" \/>/)
})
test('rejects hidden/wrong type/traversal and malformed media', () => { assert.equal(isProjectSlug('../secret'), false); assert.equal(isProjectSlug('Du An'), false); assert.equal(isPublicProjectDetailPayload({ ...good, project: { ...good.project, coverImage: '/upload/news/../secret.jpg' } }), false); assert.equal(isPublicProjectDetailPayload({ ...good, project: { ...good.project, relatedEquipment: [{ ...good.project.relatedEquipment[0], url: '/secret/' }] } }), false) })
test('project CTA carries Admin trust items and rejects unsafe icon paths', () => {
  const cta = { eyebrow: '', title: 'Cần tư vấn?', description: '', buttonLabel: 'Liên hệ', image: '', hotline: '', trust: [{ label: 'Tư vấn tận tâm', icon: '/upload/news/trust.png', faIcon: 'fa-check' }] }
  assert.equal(isPublicProjectDetailPayload({ ...good, project: { ...good.project, cta } }), true)
  assert.equal(isPublicProjectDetailPayload({ ...good, project: { ...good.project, cta: { ...cta, trust: [{ ...cta.trust[0], icon: 'https://evil.test/icon.png' }] } } }), false)
  assert.match(projectDetailPage, /project\.cta\.trust/)
  assert.doesNotMatch(projectDetailPage, /<i class="fas/)
  assert.match(projectDetailPage, /<UIcon[^>]+name="i-lucide-check"/)
  assert.match(projectDetailPage, /<UIcon[^>]+name="i-lucide-headset"/)
  assert.match(projectDetailPage, /<UIcon[^>]+name="i-lucide-settings"/)
  assert.match(projectDetailPage, /<UIcon v-else name="i-lucide-shield-check"/)
  assert.match(readFileSync(new URL('../nuxt.config.ts', import.meta.url), 'utf8'), /modules: \['@nuxt\/ui'\]/)
  const endpoint = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/api/pts_v1_project_detail.php', import.meta.url), 'utf8')
  assert.match(endpoint, /project-trust/)
  assert.match(endpoint, /'trust' => ptsProjectDetailTrustDto\(\(array\)\$trustRows, ROOT\)/)
})
test('does not allow unsanitized editor HTML, wrong sanitizer metadata or external image paths', () => {
  assert.equal(isPublicProjectDetailPayload({ ...good, content: { ...good.content, html: '<script>alert(1)</script>' } }), false)
  assert.equal(isPublicProjectDetailPayload({ ...good, content: { rendered: false, reason: 'ckeditor_content_deferred_until_sanitizer_gate' } }), false)
  assert.equal(isPublicProjectDetailPayload({ ...good, project: { ...good.project, gallery: [{ type: 'image', src: 'https://evil.test/a.jpg', caption: '' }] } }), false)
})
