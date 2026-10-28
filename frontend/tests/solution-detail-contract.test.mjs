import { test } from 'node:test'
import assert from 'node:assert/strict'
import { isPublicSolutionDetailPayload } from '../shared/contracts/public-solution-detail.ts'

const sanitized = { format: 'sanitized-html', html: '<p>Đoạn thật</p>', sanitizedBy: 'pts_public_richtext', sanitizerVersion: 1 }
const good = {
  schemaVersion: 1, locale: 'vi', path: '/giai-phap/dien-mat-troi/',
  solution: { id: 1, title: 'Điện mặt trời', slug: 'dien-mat-troi', url: '/giai-phap/dien-mat-troi/', summary: '', image: '/upload/news/a.jpg', content: sanitized, extraContent: { ...sanitized, html: '' }, gallery: ['/upload/news/b.jpg'] },
  navigation: { allUrl: '/giai-phap/', items: [{ title: 'Điện mặt trời', slug: 'dien-mat-troi', url: '/giai-phap/dien-mat-troi/', image: '', active: true }] },
  equipment: [{ name: 'Biến tần', description: '', url: '/danh-muc-san-pham/bien-tan/', image: '' }],
  cta: { label: 'Nhận tư vấn giải pháp', href: '/lien-he/#yeu-cau-tu-van' },
  editorial: { rendered: false, reason: 'toc_generation_not_implemented' },
  parity: { toc: { included: false, reason: 'toc_generation_not_implemented' } },
}

test('accepts safe solution detail DTO', () => assert.equal(isPublicSolutionDetailPayload(good), true))
test('rejects executable or external media', () => assert.equal(isPublicSolutionDetailPayload({ ...good, solution: { ...good.solution, content: { ...sanitized, html: '<script>bad</script>' } } }), false))
test('rejects non-legacy CTA and invalid navigation URL', () => {
  assert.equal(isPublicSolutionDetailPayload({ ...good, cta: { ...good.cta, href: 'https://example.test' } }), false)
  assert.equal(isPublicSolutionDetailPayload({ ...good, navigation: { ...good.navigation, items: [{ ...good.navigation.items[0], url: '/giai-phap/../../bad/' }] } }), false)
})
test('rejects wrong sanitizer metadata on either content field', () => {
  assert.equal(isPublicSolutionDetailPayload({ ...good, solution: { ...good.solution, content: { ...sanitized, sanitizedBy: 'other' } } }), false)
  assert.equal(isPublicSolutionDetailPayload({ ...good, solution: { ...good.solution, extraContent: { ...sanitized, sanitizerVersion: 2 } } }), false)
})
