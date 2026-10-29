import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isPublicProjectsPayload } from '../shared/contracts/public-projects.ts'

const category = { id: 7, name: 'Cá nhân', description: 'Dự án cá nhân', slug: 'du-an-ca-nhan', url: '/danh-muc-du-an/du-an-ca-nhan/', canonicalPath: '/du-an-ca-nhan', image: '' }
const project = { id: 9, title: 'Công trình thực tế', slug: 'cong-trinh-thuc-te', url: '/cong-trinh-thuc-te/', description: 'Mô tả CMS', address: 'Gia Lai', image: '/upload/news/project.jpg', category, featured: false }
const good = {
  schemaVersion: 1, locale: 'vi', path: '/du-an-da-thuc-hien/', title: 'Dự án đã thực hiện', category: null,
  intro: { visible: true, title: 'Dự án đã thực hiện', description: 'Mô tả trang' },
  featuredBenefits: [{ label: 'Tư vấn', image: '' }, { label: 'Thi công', image: '' }],
  categories: [category], featured: { ...project, featured: true }, projects: [project],
  pagination: { page: 1, perPage: 12, total: 2 },
  cta: { title: 'Liên hệ', description: 'Tư vấn', buttonLabel: 'Liên hệ ngay', image: '', hotline: null },
  labels: { all: 'Tất cả', filters: 'Lọc dự án', featured: 'Dự án nổi bật', view: 'Xem dự án', empty: 'Trống', cta: 'Liên hệ' },
}

test('accepts the projects DTO and nullable CMS CTA', () => {
  assert.equal(isPublicProjectsPayload(good), true)
  assert.equal(isPublicProjectsPayload({ ...good, featured: null, cta: null, projects: [] }), true)
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
