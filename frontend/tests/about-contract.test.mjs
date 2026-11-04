import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { isPublicAboutPayload } from '../shared/contracts/public-about.ts'

const text = { title: 'CMS title', description: 'CMS description', image: '' }
const good = {
  schemaVersion: 2, locale: 'vi', path: '/gioi-thieu/', title: 'Giới thiệu Phúc Thịnh Solar',
  story: { eyebrow: 'Hành trình phát triển', title: 'Giới thiệu Phúc Thịnh Solar', intro: 'Nội dung CMS', body: '', image: '/upload/news/story.jpg' },
  history: { title: 'Lịch sử', image: '', items: [{ year: '2020', title: 'Mốc thật', description: 'Mô tả CMS' }] },
  people: { eyebrow: 'Con người', title: 'Nhân sự', intro: 'Giới thiệu', body: '', image: '', principles: [text] },
  trust: { title: 'Vì sao', items: [text] }, compass: { title: 'Tầm nhìn', items: [] },
  certificates: { title: 'Chứng nhận', intro: 'Đang công bố', items: [{ id: 1, name: 'Chứng nhận CMS', description: '', image: '/upload/photo/cert.jpg', href: '/upload/photo/cert.jpg', featured: true }] },
  cta: { title: 'Tư vấn', description: 'Mô tả', image: '', href: null, buttonLabel: 'Nhận tư vấn ngay', hotline: null, zaloUrl: null },
  labels: { story: 'Về Phúc Thịnh Solar', history: 'Lịch sử phát triển', people: 'Con người', compass: 'Tầm nhìn và sứ mệnh', certificates: 'Năng lực và chứng nhận', timeline: 'Các mốc', certLabel: 'Chứng nhận', certAll: 'Xem tất cả', certDetail: 'Chi tiết' },
}

test('accepts the read-only About DTO with nullable CMS sections', () => {
  assert.equal(isPublicAboutPayload(good), true)
  assert.equal(isPublicAboutPayload({ ...good, story: { ...good.story, image: '' }, certificates: { ...good.certificates, items: [] } }), true)
})

test('accepts optional Admin SEO fields and rejects unsafe SEO media', () => {
  const seo = { title: 'SEO title', description: 'SEO description', keywords: 'solar', image: '/upload/seopage/about.jpg' }
  assert.equal(isPublicAboutPayload({ ...good, seo }), true)
  assert.equal(isPublicAboutPayload({ ...good, seo: { ...seo, image: '/upload/seopage/../private.jpg' } }), false)
  assert.equal(isPublicAboutPayload(good), true)
})

test('rejects invented routes, unsafe media and non-year timeline values', () => {
  assert.equal(isPublicAboutPayload({ ...good, path: '/gioi-thieu/demo/' }), false)
  assert.equal(isPublicAboutPayload({ ...good, story: { ...good.story, image: '/upload/news/../../secret.jpg' } }), false)
  assert.equal(isPublicAboutPayload({ ...good, history: { ...good.history, items: [{ ...good.history.items[0], year: '2026-01-01' }] } }), false)
})

test('accepts escaped plain text because the adapter never renders CMS HTML', () => {
  assert.equal(isPublicAboutPayload({ ...good, story: { ...good.story, intro: '<b>escaped by PHP adapter</b>' } }), true)
})

test('About trust features use the PHP two-column desktop grid', async () => {
  const [source, phpTemplate, phpCss] = await Promise.all([
    readFile(new URL('../app/components/about/AboutPreviewPage.vue', import.meta.url), 'utf8'),
    readFile(new URL('../../domains/phucthinhsolar.com/public_html/templates/about/about_tpl.php', import.meta.url), 'utf8'),
    readFile(new URL('../../domains/phucthinhsolar.com/public_html/assets/css/about-v2.css', import.meta.url), 'utf8'),
  ])
  const section = source.split('id="about-trust-title"')[1]?.split('</section>')[0] ?? ''
  assert.match(phpTemplate, /class="pts-about-trust-grid"/)
  assert.match(phpCss, /\.pts-about-trust-grid\{display:grid;grid-template-columns:repeat\(2,minmax\(0,1fr\)\)/)
  assert.match(section, /grid grid-cols-2[^\"]*max-\[800px\]:grid-cols-1/)
  assert.doesNotMatch(section, /border-t border-\[#dce4ef\]|border-b border-\[#dce4ef\]/)
})

test('does not render an empty certificate image frame when CMS media is absent', async () => {
  const source = await readFile(new URL('../app/components/about/AboutPreviewPage.vue', import.meta.url), 'utf8')
  const certificates = source.split('id="about-certificates"')[1]?.split('</section>')[0] ?? ''
  assert.match(certificates, /<button[\s\S]*?v-if="item\.image"/)
  assert.match(certificates, /:src="item\.image"/)
})

test('validates only canonical Zalo URLs and permits every published certificate item in the DTO', () => {
  assert.equal(isPublicAboutPayload({ ...good, cta: { ...good.cta, zaloUrl: 'https://zalo.me/84901234567' } }), true)
  assert.equal(isPublicAboutPayload({ ...good, cta: { ...good.cta, zaloUrl: 'https://evil.example/84901234567' } }), false)
  assert.equal(isPublicAboutPayload({ ...good, cta: { ...good.cta, zaloUrl: 'https://zalo.me/123?redirect=https://evil.example' } }), false)
  assert.equal(isPublicAboutPayload({ ...good, certificates: { ...good.certificates, items: [...good.certificates.items, { id: 2, name: 'Chứng nhận thứ hai', description: '', image: '', href: null, featured: false }] } }), true)
})

test('About CTA resolves a null CMS modal link to the contact form and keeps hotline/Zalo actions', async () => {
  const source = await readFile(new URL('../app/components/about/AboutPreviewPage.vue', import.meta.url), 'utf8')
  const cta = source.split('id="about-cta-title"')[1]?.split('</section>')[0] ?? ''
  assert.equal(good.cta.href, null)
  // Owner decision 2026-11-04: CTA actions are rendered by the shared PublicCtaBanner.
  const banner = await readFile(new URL('../app/components/PublicCtaBanner.vue', import.meta.url), 'utf8')
  assert.match(cta, /:fallback-href="about\.cta\.href \|\| '\/lien-he\/#yeu-cau-tu-van'"/)
  assert.match(cta, /:hotline="about\.cta\.hotline\?\.text"/)
  assert.match(cta, /:zalo="about\.cta\.zaloUrl/)
  assert.match(banner, /v-if="phoneHref"[\s\S]*:href="phoneHref"/)
  assert.match(banner, /v-if="zalo"[\s\S]*:href="zalo"[\s\S]*target="_blank"[\s\S]*rel="noopener noreferrer"/)
})

test('About PHP projection carries CMS Zalo and does not truncate published certificates', async () => {
  const api = await readFile(new URL('../../domains/phucthinhsolar.com/public_html/api/pts_v1_about.php', import.meta.url), 'utf8')
  const helper = await readFile(new URL('../../domains/phucthinhsolar.com/public_html/libraries/pts_public_about_api.php', import.meta.url), 'utf8')
  assert.match(api, /\$certificates\s*=\s*\$loadRows\([\s\S]*find_in_set\('hienthi',status\)/)
  assert.match(api, /foreach\(\$allCertificates as \$row\)/)
  assert.match(api, /\$certificateCards\s*=\s*array_slice\(\$featuredCertificates\s*\?:\s*\$certificates,\s*0,\s*3\)/)
  assert.match(api, /\$func->parsePhone\(\$zaloValue\)/)
  assert.match(api, /#_seopage where type=\? limit 0,1', array\('gioi-thieu'\)/)
  assert.match(helper, /'seo'\s*=>\s*array\(/)
  assert.match(helper, /'schemaVersion'\s*=>\s*2/)
})

test('About page prefers Admin SEO and uses its image for social metadata', async () => {
  const source = await readFile(new URL('../app/pages/gioi-thieu.vue', import.meta.url), 'utf8')
  assert.match(source, /about\.value\?\.seo\?\.title\s*\|\|\s*about\.value\?\.title/)
  assert.match(source, /about\.value\?\.seo\?\.description\s*\|\|\s*about\.value\?\.story\.intro/)
  assert.match(source, /about\.value\?\.seo\?\.image\s*\|\|\s*undefined/)
})

test('About page provides a keyboard-accessible full certificate gallery', async () => {
  const source = await readFile(new URL('../app/components/about/AboutPreviewPage.vue', import.meta.url), 'utf8')
  assert.match(source, /about\.labels\.certAll/)
  assert.match(source, /@click="openAllCertificates"/)
  assert.match(source, /<dialog[\s\S]*aria-modal="true"[\s\S]*aria-labelledby="about-certificate-dialog-title"/)
  assert.match(source, /@keydown\.left\.prevent="previousCertificate"/)
  assert.match(source, /@keydown\.right\.prevent="nextCertificate"/)
  assert.match(source, /aria-label="Chứng nhận tiếp theo"/)
  assert.match(source, /aria-label="Đóng xem chứng nhận"/)
  assert.match(source, /const galleryCertificates = computed\(\(\) => props\.about\.certificates\.items\)/)
  assert.match(source, /:zalo="about\.cta\.zaloUrl/)
})

test('About public and preview pages expose one main landmark without nesting it in the content component', async () => {
  const [component, publicPage, previewPage] = await Promise.all([
    readFile(new URL('../app/components/about/AboutPreviewPage.vue', import.meta.url), 'utf8'),
    readFile(new URL('../app/pages/gioi-thieu.vue', import.meta.url), 'utf8'),
    readFile(new URL('../app/pages/migration-about-preview.vue', import.meta.url), 'utf8'),
  ])
  assert.match(component, /<div class="overflow-x-clip text-navy-800">/)
  assert.equal((publicPage.match(/<main\b/g) ?? []).length, 1)
  assert.match(previewPage, /<main>[\s\S]*<AboutPreviewPage v-else/)
})
