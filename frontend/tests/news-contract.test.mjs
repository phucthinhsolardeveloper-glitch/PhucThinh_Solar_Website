import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isPublicNewsPayload } from '../shared/contracts/public-news.ts'

const category = { id: 4, name: 'Kiến thức điện mặt trời', description: 'Mô tả nhóm', slug: 'kien-thuc-dien-mat-troi', url: '/kien-thuc-dien-mat-troi/', canonicalPath: '/kien-thuc-dien-mat-troi', image: '' }
const article = { id: 8, title: 'Bài viết CMS', slug: 'bai-viet-cms', url: '/bai-viet-cms/', description: 'Mô tả đã làm sạch', date: '2026-10-26', image: '/upload/news/article.webp', readingMinutes: 2, category, featured: false }
const good = { schemaVersion: 1, locale: 'vi', path: '/tin-tuc/', title: 'Kiến thức', description: 'Giới thiệu CMS', category: null, categories: [category], featured: [{ ...article, featured: true }], articles: [article], pagination: { page: 1, perPage: 6, total: 7 }, intro: { title: 'Kiến thức', description: 'Giới thiệu CMS' }, cta: null, labels: { all: 'Tất cả', search: 'Tìm kiếm bài viết', featured: 'Nổi bật', results: 'Bài viết', empty: 'Trống', reset: 'Xem tất cả', read: 'phút đọc', pages: 'Trang', readMore: 'Đọc tiếp', ctaPhone: 'Gọi' } }

test('accepts the PHP news DTO and nullable CMS CTA', () => assert.equal(isPublicNewsPayload(good), true))
test('rejects unsafe canonical paths and raw HTML', () => {
  assert.equal(isPublicNewsPayload({ ...good, articles: [{ ...article, url: 'https://example.test/news' }] }), false)
  assert.equal(isPublicNewsPayload({ ...good, articles: [{ ...article, title: '<script>x</script>' }] }), false)
  assert.equal(isPublicNewsPayload({ ...good, articles: [{ ...article, image: '/upload/news/../../secret.jpg' }] }), false)
})
test('rejects wrong legacy page size and malformed visibility DTO', () => {
  assert.equal(isPublicNewsPayload({ ...good, pagination: { ...good.pagination, perPage: 12 } }), false)
  assert.equal(isPublicNewsPayload({ ...good, featured: [{ ...article, featured: false }] }), true)
  assert.equal(isPublicNewsPayload({ ...good, articles: [{ ...article, readingMinutes: -1 }] }), false)
})

test('accepts resolved multi-level category metadata only when its leaf slug matches', () => {
  const selected = { ...category, slug: 'ung-dung', url: '/ung-dung/', canonicalPath: '/ung-dung', type: 'tin-tuc', level: 'item', path: 'kien-thuc/dien-mat-troi/ung-dung' }
  assert.equal(isPublicNewsPayload({ ...good, category: selected }), true)
  assert.equal(isPublicNewsPayload({ ...good, category: { ...selected, path: 'kien-thuc/dien-mat-troi/khac' } }), false)
  assert.equal(isPublicNewsPayload({ ...good, category: { ...selected, type: 'du-an' } }), false)
})
