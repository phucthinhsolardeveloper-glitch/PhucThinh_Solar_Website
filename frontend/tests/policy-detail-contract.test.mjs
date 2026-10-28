import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isPublicPolicyDetail } from '../shared/contracts/public-policy-detail.ts'

const good = {
  schemaVersion: 1,
  locale: 'vi',
  id: 4,
  title: 'Chính sách bảo hành',
  slug: 'chinh-sach-bao-hanh',
  canonical: '/ho-tro/chinh-sach-bao-hanh/',
  description: '',
  date: '2026-10-01',
  author: { name: 'Phúc Thịnh Solar' },
  content: { format: 'sanitized-html', html: '<p>Nội dung an toàn</p>', sanitizedBy: 'pts_public_richtext', sanitizerVersion: 1 },
  related: [{ id: 5, title: 'Chính sách liên quan', slug: 'chinh-sach-lien-quan', canonical: '/chinh-sach-lien-quan/', date: '2026-09-20', description: '', image: '/upload/news/policy.webp' }],
  seo: { title: 'Chính sách bảo hành', description: '', keywords: '', canonical: '/ho-tro/chinh-sach-bao-hanh/' },
}
test('accepts published support policy DTO', () => assert.equal(isPublicPolicyDetail(good), true))
test('rejects wrong canonical and executable rich text', () => {
  assert.equal(isPublicPolicyDetail({ ...good, canonical: '/chinh-sach-bao-hanh/' }), false)
  assert.equal(isPublicPolicyDetail({ ...good, content: { ...good.content, html: '<script>alert(1)</script>' } }), false)
})
test('requires safe author and related policy entries', () => {
  assert.equal(isPublicPolicyDetail({ ...good, author: { name: '' } }), false)
  assert.equal(isPublicPolicyDetail({ ...good, related: [{ ...good.related[0], image: '/upload/news/../../private.jpg' }] }), false)
})
