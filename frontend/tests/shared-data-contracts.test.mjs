import { test } from 'node:test'
import assert from 'node:assert/strict'
import {
  isPagination,
  isPublicImage,
  isPublicUrl,
  isRecord,
  isRelativePublicUrl,
  isSlug,
  isText,
} from '../shared/contracts/core.ts'
import { isPublicAboutPayload } from '../shared/contracts/public-about.ts'
import { isPublicContactPayload } from '../shared/contracts/public-contact.ts'
import { isPublicProjectsPayload } from '../shared/contracts/public-projects.ts'

const aboutSample = {
  schemaVersion: 2,
  locale: 'vi',
  path: '/gioi-thieu/',
  title: 'Giới thiệu Phúc Thịnh Solar',
  story: { eyebrow: 'Hành trình phát triển', title: 'Giới thiệu Phúc Thịnh Solar', intro: 'Nội dung CMS', body: '', image: '/upload/news/about-phuc-thinh-showroom-20260916.jpg' },
  history: { title: '', image: '/upload/news/history.jpg', items: [{ year: '2019', title: '', description: 'Nội dung lịch sử CMS' }] },
  people: { eyebrow: 'Con người là nền tảng', title: 'Nhân sự Phúc Thịnh Solar', intro: 'Nội dung CMS', body: '', image: '/upload/photo/team.png', principles: [] },
  trust: { title: 'Vì sao', items: [{ title: '', description: 'Cam kết CMS', image: '/upload/news/trust.png' }] },
  compass: { title: 'Tầm nhìn', items: [] },
  certificates: { title: 'Chứng nhận & năng lực', intro: 'Nội dung CMS', items: [{ id: 101, name: 'HUAWEI', description: '', image: '/upload/photo/cert.jpg', href: '/upload/photo/cert.jpg', featured: true }] },
  cta: { title: 'Cần tư vấn?', description: 'Nội dung CMS', image: '', href: null, buttonLabel: 'Nhận tư vấn', hotline: null, zaloUrl: null },
  labels: { story: 'Về Phúc Thịnh Solar', history: 'Lịch sử', people: 'Con người', compass: 'Tầm nhìn', certificates: 'Chứng nhận', timeline: 'Các mốc', certLabel: 'Chứng nhận', certAll: 'Xem tất cả', certDetail: 'Chi tiết' },
}

const category = { id: 6, name: 'Cá nhân', description: '', slug: 'du-an-ca-nhan', url: '/danh-muc-du-an/du-an-ca-nhan/', canonicalPath: '/du-an-ca-nhan', image: '/upload/news/solar-house.png' }
const project = { id: 75, title: 'Dự án điện NLMT hộ anh Quân', slug: 'du-an-dien-nlmt-ho-anh-quan', url: '/du-an-dien-nlmt-ho-anh-quan/', description: '', address: 'Gia Lai', image: '/upload/news/project.png', category, featured: false }
const projectsSample = {
  schemaVersion: 1,
  locale: 'vi',
  path: '/du-an-da-thuc-hien/',
  title: 'Dự án đã thực hiện',
  intro: { visible: true, title: 'Dự án đã thực hiện', description: 'Nội dung CMS' },
  featuredBenefits: [{ label: 'Tư vấn', image: '' }],
  category: null,
  categories: [category],
  featured: { ...project, featured: true },
  projects: [project],
  pagination: { page: 1, perPage: 12, total: 1 },
  cta: null,
  labels: { all: 'Tất cả', filters: 'Lọc dự án', featured: 'Dự án nổi bật', view: 'Xem dự án', empty: 'Chưa có dự án', cta: 'Liên hệ' },
}

const contactSample = {
  schemaVersion: 1,
  locale: 'vi',
  path: '/lien-he/',
  seo: { title: 'Liên hệ Phúc Thịnh Solar', description: 'Mô tả CMS' },
  title: 'Liên hệ Phúc Thịnh Solar',
  intro: 'Nội dung CMS',
  visual: { name: 'Tư vấn đúng thiết bị', description: 'Nội dung CMS', href: '#yeu-cau-tu-van', image: '/assets/images/site-visuals/contact-consultation.jpg' },
  contact: { company: '', hotline: '', email: '', address: '', workingHours: '' },
  locations: [{ id: 275, name: 'Trụ sở chính', address: 'Địa chỉ CMS', mapUrl: '' }],
  labels: { locations: 'Hệ thống chi nhánh', formCta: 'Liên hệ qua form PHP', formNote: 'Biểu mẫu vẫn ở PHP.' },
}

test('shared primitives keep records/text strict without rejecting CMS markup', () => {
  assert.equal(isRecord({}), true)
  assert.equal(isRecord([]), false)
  assert.equal(isText('<b>plain text from PHP</b>'), true)
  for (const value of ['x\u0000y', 'x\u0085y', 'x\u200By', 'x\u2028y']) assert.equal(isText(value), false, JSON.stringify(value))
})

test('shared URL and media primitives reject dangerous public values', () => {
  assert.equal(isPublicUrl('/lien-he/'), true)
  assert.equal(isPublicUrl('https://example.test/path'), true)
  assert.equal(isRelativePublicUrl('#yeu-cau-tu-van'), true)
  for (const value of ['javascript:alert(1)', 'data:text/html,x', '//evil.test/path', '/upload/../secret.jpg', '/x\u200By']) {
    assert.equal(isPublicUrl(value), false, value)
  }
  assert.equal(isPublicImage('/upload/news/photo.webp', ['/upload/news/']), true)
  assert.equal(isPublicImage('/assets/images/site-visuals/contact.jpg', ['/assets/images/site-visuals/'], false, ['jpe?g', 'png', 'gif', 'webp']), true)
  for (const value of ['/upload/news/photo.svg', '/upload/news/../../secret.jpg', 'data:image/png,x', '//cdn.test/photo.jpg']) {
    assert.equal(isPublicImage(value, ['/upload/news/']), false, value)
  }
})

test('shared slug and pagination primitives reject malformed numeric/public identifiers', () => {
  assert.equal(isSlug('du-an-ca-nhan'), true)
  for (const value of ['Du-an', 'du_an', '../du-an', '']) assert.equal(isSlug(value), false, value)
  assert.equal(isPagination({ page: 1, perPage: 12, total: 0 }), true)
  for (const pagination of [
    { page: 0, perPage: 12, total: 0 },
    { page: 1.5, perPage: 12, total: 0 },
    { page: 1, perPage: 0, total: 0 },
    { page: 1, perPage: 12, total: -1 },
    { page: 1, perPage: Number.MAX_SAFE_INTEGER + 1, total: 0 },
  ]) assert.equal(isPagination(pagination), false, JSON.stringify(pagination))
})

test('three migrated validators accept local PHP sample shapes and empty CMS variants', () => {
  assert.equal(isPublicAboutPayload(aboutSample), true)
  assert.equal(isPublicAboutPayload({ ...aboutSample, people: { ...aboutSample.people, principles: [] }, certificates: { ...aboutSample.certificates, items: [] } }), true)
  assert.equal(isPublicProjectsPayload(projectsSample), true)
  assert.equal(isPublicProjectsPayload({ ...projectsSample, featured: null, projects: [], categories: [], cta: null }), true)
  assert.equal(isPublicContactPayload(contactSample), true)
  assert.equal(isPublicContactPayload({ ...contactSample, visual: null, locations: [] }), true)
})

test('three migrated validators reject unsafe URL/media/control variants', () => {
  assert.equal(isPublicAboutPayload({ ...aboutSample, certificates: { ...aboutSample.certificates, items: [{ ...aboutSample.certificates.items[0], href: 'javascript:alert(1)' }] } }), false)
  assert.equal(isPublicAboutPayload({ ...aboutSample, story: { ...aboutSample.story, image: '/upload/news/logo.svg' } }), false)
  assert.equal(isPublicAboutPayload({ ...aboutSample, cta: { ...aboutSample.cta, href: 'https://example.test/\u2028x' } }), false)

  assert.equal(isPublicProjectsPayload({ ...projectsSample, projects: [{ ...project, slug: 'du-an-\u200b' }] }), false)
  assert.equal(isPublicProjectsPayload({ ...projectsSample, projects: [{ ...project, image: '/upload/news/project.svg' }] }), false)
  assert.equal(isPublicProjectsPayload({ ...projectsSample, pagination: { page: 1, perPage: 12.5, total: 1 } }), false)

  assert.equal(isPublicContactPayload({ ...contactSample, visual: { ...contactSample.visual, href: '//evil.test' } }), false)
  assert.equal(isPublicContactPayload({ ...contactSample, visual: { ...contactSample.visual, image: '/assets/images/site-visuals/contact.svg' } }), false)
  assert.equal(isPublicContactPayload({ ...contactSample, locations: [{ ...contactSample.locations[0], mapUrl: 'data:text/html,x' }] }), false)
})
