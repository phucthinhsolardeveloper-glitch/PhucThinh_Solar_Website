import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isPublicProjectsPayload } from '../shared/contracts/public-projects.ts'

const category = { id: 7, name: 'Hộ gia đình', slug: 'du-an-ho-gia-dinh', description: 'Ứng dụng gia đình', url: '/danh-muc-du-an/du-an-ho-gia-dinh/', image: '' }
const project = { id: 9, title: 'Công trình thực tế', slug: 'cong-trinh-thuc-te', url: '/cong-trinh-thuc-te/', description: 'Mô tả CMS', address: 'Gia Lai', image: '/upload/news/project.jpg', category, featured: false }
const good = {
  schemaVersion: 1, locale: 'vi', path: '/du-an-da-thuc-hien/', title: 'Dự án đã thực hiện', intro: { visible: true, eyebrow: 'Công trình thực tế', title: 'Dự án đã thực hiện', description: 'Giới thiệu CMS' }, category: null,
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
})

test('rejects malformed pagination and non-CMS rich content shape', () => {
  assert.equal(isPublicProjectsPayload({ ...good, pagination: { ...good.pagination, page: 0 } }), false)
  assert.equal(isPublicProjectsPayload({ ...good, cta: { ...good.cta, title: '<script>x</script>' } }), true)
})

test('accepts PHP bare-slug project category chains only at the matching depth', () => {
  const selected = { ...category, slug: 'dien-mat-troi', url: '/dien-mat-troi/', type: 'du-an', level: 'cat', path: 'cong-trinh/dien-mat-troi' }
  assert.equal(isPublicProjectsPayload({ ...good, category: selected }), true)
  assert.equal(isPublicProjectsPayload({ ...good, category: { ...selected, path: 'cong-trinh/khac' } }), false)
})
