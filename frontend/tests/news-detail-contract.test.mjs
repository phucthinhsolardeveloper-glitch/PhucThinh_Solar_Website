import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isNewsDetailSlug, isPublicNewsDetailPayload } from '../shared/contracts/public-news-detail.ts'

const article = { schemaVersion: 1, locale: 'vi', id: 8, title: 'Bài viết CMS', slug: 'bai-viet-cms', canonical: '/bai-viet-cms/', date: '2026-10-26', author: { name: 'Phúc Thịnh Solar' }, description: 'Mô tả', cover: '/upload/news/article.webp', content: { format: 'sanitized-html', html: '<p>Nội dung</p>', sanitizedBy: 'pts_public_richtext', sanitizerVersion: 1 }, category: {}, media: { gallery: [{ image: '/upload/news/gallery.jpg', alt: '' }], videos: [{ url: 'https://www.youtube.com/watch?v=abc', title: '' }] }, seo: { title: 'Bài viết CMS', description: 'Mô tả', keywords: '', canonical: '/bai-viet-cms/' }, readOnly: true, legacyViewMutation: false }
const good = { article, related: [{ id: 9, title: 'Tin khác', slug: 'tin-khac', canonical: '/tin-khac/', date: '', description: '', image: '' }] }
test('accepts safe read-only detail DTO', () => assert.equal(isPublicNewsDetailPayload(good), true))
test('rejects unsafe slugs, media and raw HTML', () => {
  assert.equal(isNewsDetailSlug('../secret'), false)
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, title: '<script>x</script>' } }), false)
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, cover: '/upload/news/../../secret.jpg' } }), false)
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, media: { ...article.media, videos: [{ url: 'javascript:alert(1)', title: '' }] } } }), false)
})
test('requires no legacy view mutation and rejects wrong content contract', () => {
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, legacyViewMutation: true } }), false)
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, content: { format: 'plain-text', value: 'x', richTextDeferred: true } } }), false)
})
test('rejects content that carries unsanitized markup or wrong sanitizer metadata', () => {
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, content: { ...article.content, html: '<script>alert(1)</script>' } } }), false)
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, content: { ...article.content, sanitizedBy: 'something-else' } } }), false)
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, content: { ...article.content, sanitizerVersion: 2 } } }), false)
})
