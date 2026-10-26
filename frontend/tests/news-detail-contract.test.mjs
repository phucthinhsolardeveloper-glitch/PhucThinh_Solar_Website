import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isNewsDetailSlug, isPublicNewsDetailPayload } from '../shared/contracts/public-news-detail.ts'

const article = { schemaVersion: 1, locale: 'vi', id: 8, title: 'Bài viết CMS', slug: 'bai-viet-cms', canonical: '/bai-viet-cms/', date: '2026-10-26', author: { name: 'Phúc Thịnh Solar' }, description: 'Mô tả', cover: '/upload/news/article.webp', content: { format: 'plain-text', value: 'Nội dung', richTextDeferred: true }, category: {}, media: { gallery: [{ image: '/upload/news/gallery.jpg', alt: '' }], videos: [{ url: 'https://www.youtube.com/watch?v=abc', title: '' }] }, seo: { title: 'Bài viết CMS', description: 'Mô tả', keywords: '', canonical: '/bai-viet-cms/' }, readOnly: true, legacyViewMutation: false }
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
  assert.equal(isPublicNewsDetailPayload({ ...good, article: { ...article, content: { format: 'html', value: '<p>x</p>', richTextDeferred: false } } }), false)
})
