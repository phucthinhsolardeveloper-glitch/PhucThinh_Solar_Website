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
  content: { format: 'sanitized-html', html: '<p>Nội dung an toàn</p>', sanitizedBy: 'pts_public_richtext', sanitizerVersion: 1 },
  seo: { title: 'Chính sách bảo hành', description: '', keywords: '', canonical: '/ho-tro/chinh-sach-bao-hanh/' },
}
test('accepts published support policy DTO', () => assert.equal(isPublicPolicyDetail(good), true))
test('rejects wrong canonical and executable rich text', () => {
  assert.equal(isPublicPolicyDetail({ ...good, canonical: '/chinh-sach-bao-hanh/' }), false)
  assert.equal(isPublicPolicyDetail({ ...good, content: { ...good.content, html: '<script>alert(1)</script>' } }), false)
})
