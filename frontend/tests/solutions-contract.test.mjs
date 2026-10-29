import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isPublicSolutionsPayload, isPublicSolutionCategoryPayload } from '../shared/contracts/public-solutions.ts'

const good = {
  schemaVersion: 1, locale: 'vi', path: '/giai-phap/', title: 'Chọn đúng giải pháp cho nhu cầu của bạn',
  intro: { eyebrow: 'Giải pháp theo nhu cầu', title: 'Giải pháp', description: 'Mô tả', image: '', links: { consult: { label: 'Nhận tư vấn', href: '#ptsContactModal' }, explore: { label: 'Khám phá', href: '/giai-phap/#cac-giai-phap' } } },
  cards: [{ title: 'Điện mặt trời', description: '', slug: 'dien-mat-troi', url: '/giai-phap/dien-mat-troi/', image: '/assets/images/solution.jpg', actionLabel: 'Xem giải pháp' }],
  equipment: [{ name: 'Biến tần', description: '', url: '/danh-muc-san-pham/bien-tan/', image: '' }],
  allProductsUrl: '/san-pham/', labels: { choices: 'Khám phá', equipment: 'Thiết bị', allProducts: 'Tất cả', empty: 'Trống', consult: 'Tư vấn', phone: 'Gọi' },
  cta: { title: 'Tư vấn', description: 'Mô tả', buttonLabel: 'Nhận tư vấn', image: '/upload/cta.jpg', links: { consult: { label: 'Nhận tư vấn', href: '#ptsContactModal' } }, hotline: null },
  editorial: {
    content: { format: 'sanitized-html', html: '<h2 id="chon-giai-phap">Nội dung tư vấn</h2><p>Đã qua <strong>sanitizer</strong>.</p>', sanitizedBy: 'pts_public_richtext', sanitizerVersion: 1 },
    toc: { rendered: true, items: [{ id: 'solution-section-1', label: 'Nội dung tư vấn', level: 2 }] },
  },
}

test('accepts the PHP solutions DTO', () => assert.equal(isPublicSolutionsPayload(good), true))
test('accepts hidden intro and CTA as null', () => assert.equal(isPublicSolutionsPayload({ ...good, intro: null, cta: null }), true))
test('rejects unsafe image paths and arbitrary links', () => {
  assert.equal(isPublicSolutionsPayload({ ...good, cards: [{ ...good.cards[0], image: 'https://example.test/x' }] }), false)
  assert.equal(isPublicSolutionsPayload({ ...good, intro: { ...good.intro, links: { ...good.intro.links, explore: { label: 'x', href: 'https://example.test' } } } }), false)
})
test('accepts sanitized editorial content but rejects unsafe markup or fake TOC state', () => {
  assert.equal(isPublicSolutionsPayload({ ...good, editorial: { ...good.editorial, content: { ...good.editorial.content, html: '<p onclick="bad()">x</p>' } } }), false)
  assert.equal(isPublicSolutionsPayload({ ...good, editorial: { ...good.editorial, toc: { rendered: true, items: [{ id: '../bad', label: 'x', level: 2 }] } } }), false)
  assert.equal(isPublicSolutionsPayload({ ...good, editorial: { ...good.editorial, toc: { rendered: false, reason: 'toc_generation_not_implemented' } } }), false)
})
test('rejects too many equipment items', () => {
  assert.equal(isPublicSolutionsPayload({ ...good, equipment: Array(5).fill(good.equipment[0]) }), false)
})

test('validates solution taxonomy DTOs on bare-slug canonical URLs', () => {
  const category = { schemaVersion: 1, entity: 'solution-category', slug: 'dien-mat-troi', canonical: '/dien-mat-troi/', category: { id: 3, slug: 'dien-mat-troi', name: 'Điện mặt trời', description: '', image: '', type: 'giai-phap', level: 'cat', path: 'giai-phap/dien-mat-troi' }, title: 'Điện mặt trời', description: 'Giới thiệu', cards: [{ title: 'Gia đình', description: '', slug: 'gia-dinh', url: '/giai-phap/gia-dinh/', image: '', actionLabel: 'Xem giải pháp' }], pagination: { page: 1, perPage: 12, total: 1 }, cta: null, labels: { choices: 'Giải pháp', choice: 'Xem', empty: 'Trống', consult: 'Tư vấn' } }
  assert.equal(isPublicSolutionCategoryPayload(category), true)
  assert.equal(isPublicSolutionCategoryPayload({ ...category, category: { ...category.category, path: 'giai-phap/khong-khop' } }), false)
  assert.equal(isPublicSolutionCategoryPayload({ ...category, canonical: '/giai-phap/dien-mat-troi/' }), false)
})
