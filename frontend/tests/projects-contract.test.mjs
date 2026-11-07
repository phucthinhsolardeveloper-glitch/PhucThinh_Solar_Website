import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { isPublicProjectsPayload } from '../shared/contracts/public-projects.ts'

const category = { id: 7, name: 'Cá nhân', description: 'Dự án cá nhân', slug: 'du-an-ca-nhan', url: '/danh-muc-du-an/du-an-ca-nhan/', canonicalPath: '/du-an-ca-nhan', image: '' }
const project = { id: 9, title: 'Công trình thực tế', slug: 'cong-trinh-thuc-te', url: '/cong-trinh-thuc-te/', description: 'Mô tả CMS', address: 'Gia Lai', image: '/upload/news/project.jpg', category, featured: false }
const good = {
  schemaVersion: 1, locale: 'vi', path: '/du-an-da-thuc-hien/', title: 'Dự án đã thực hiện', category: null,
  intro: { visible: true, title: 'Dự án đã thực hiện', description: 'Mô tả trang' },
  featuredBenefits: [{ label: 'Tư vấn', image: '' }, { label: 'Thi công', image: '' }],
  categories: [category], featured: { ...project, featured: true }, projects: [project],
  pagination: { page: 1, perPage: 12, total: 2 },
  cta: { eyebrow: 'Đồng hành', title: 'Liên hệ', description: 'Tư vấn', buttonLabel: 'Liên hệ ngay', image: '', trust: [{ label: 'Tư vấn', image: '', icon: 'headset' }], hotline: null },
  labels: { all: 'Tất cả', filters: 'Lọc dự án', featured: 'Dự án nổi bật', view: 'Xem dự án', empty: 'Trống', cta: 'Liên hệ' },
}
const projectsApi = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/api/pts_v1_projects.php', import.meta.url), 'utf8')
const projectsProjection = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/libraries/pts_public_projects_api.php', import.meta.url), 'utf8')

test('accepts the projects DTO and nullable CMS CTA', () => {
  assert.equal(isPublicProjectsPayload(good), true)
  assert.equal(isPublicProjectsPayload({ ...good, featured: null, cta: null, projects: [] }), true)
  const { eyebrow, trust, ...legacyCta } = good.cta
  assert.equal(isPublicProjectsPayload({ ...good, cta: legacyCta }), true)
})

test('projects CTA keeps the PHP eyebrow and trust row in the public DTO', () => {
  assert.match(projectsApi, /title_indexvi,namevi,descvi,content1vi,photo,status/)
  assert.match(projectsApi, /project-trust/)
  assert.match(projectsProjection, /'eyebrow' => ptsProjectsPlainText/)
  assert.match(projectsProjection, /'trust' => ptsProjectsTrustDto\(\$trustRows\)/)
  assert.equal(isPublicProjectsPayload({ ...good, cta: { ...good.cta, trust: [{ label: 'x', image: '', icon: 'script' }] } }), false)
})

test('projects SEO follows the active Admin keys and overrides only metadata with safe images', () => {
  const page = readFileSync(new URL('../app/components/views/ProjectsView.vue', import.meta.url), 'utf8')
  const categoryPage = readFileSync(new URL('../app/components/projects/ProjectCategoryPage.vue', import.meta.url), 'utf8')
  assert.match(projectsApi, /#_seo where id_parent = \? and com = \? and act = \? and type = \? limit 1', array\(\(int\)\$selectedCategory\['id'\], 'news', 'man_list', 'du-an'\)/)
  assert.match(projectsApi, /#_seopage where type = \? limit 1', array\('du-an'\)/)
  assert.match(projectsApi, /select titlevi,descriptionvi,keywordsvi,photo from #_seopage/)
  assert.match(projectsApi, /ptsPublicSeoDto\(is_array\(\$seoRow\).*UPLOAD_NEWS_L, \$selectedCategory\['photo'\]/)
  assert.match(projectsProjection, /'seo' => array\(/)
  assert.match(page, /projects\.value\?\.seo\?\.title \|\| projects\.value\?\.title/)
  assert.match(page, /projects\.value\?\.seo\?\.image \|\| undefined/)
  assert.match(categoryPage, /publicSeoTitle\([\s\S]*category\?\.seo\?\.title \|\| projects\.value\?\.category\?\.name/)
  assert.match(categoryPage, /category\?\.seo\?\.image \|\| projects\.value\?\.category\?\.image/)
  assert.match(categoryPage, /name: 'keywords'/)
})

test('rejects invented or unsafe public paths', () => {
  assert.equal(isPublicProjectsPayload({ ...good, projects: [{ ...project, url: 'https://example.test/project' }] }), false)
  assert.equal(isPublicProjectsPayload({ ...good, featured: { ...good.featured, image: '/upload/news/../../secret.jpg' } }), false)
  assert.equal(isPublicProjectsPayload({ ...good, category: { ...category, url: '/du-an-da-thuc-hien/' } }), false)
  assert.equal(isPublicProjectsPayload({ ...good, category: { ...category, canonicalPath: '/du-an-ca-nhan/' } }), false)
})

test('rejects malformed pagination and non-CMS rich content shape', () => {
  assert.equal(isPublicProjectsPayload({ ...good, pagination: { ...good.pagination, page: 0 } }), false)
  assert.equal(isPublicProjectsPayload({ ...good, cta: { ...good.cta, title: '<script>x</script>' } }), true)
  assert.equal(isPublicProjectsPayload({ ...good, intro: { ...good.intro, visible: 'yes' } }), false)
  assert.equal(isPublicProjectsPayload({ ...good, featuredBenefits: [{ label: '', image: '' }] }), false)
})
