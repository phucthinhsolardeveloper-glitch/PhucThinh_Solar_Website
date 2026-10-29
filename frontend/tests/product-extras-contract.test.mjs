import { test } from 'node:test'
import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { isPublicProductExtrasPayload } from '../shared/contracts/public-product-extras.ts'

const good = {
  schemaVersion: 1,
  locale: 'vi',
  productId: 12,
  profileKey: 'lighting',
  csrfToken: 'a'.repeat(64),
  videoEmbedUrl: 'https://www.youtube-nocookie.com/embed/abcdefghijk',
  warranty: { summary: '24 tháng', override: '', policies: [{ label: 'Chính sách bảo hành', href: '/ho-tro/chinh-sach-bao-hanh/' }] },
  related: [],
  reviews: {
    total: 1,
    average: 5,
    counts: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 1 },
    items: [{ id: 1, title: 'Tốt', content: 'Sản phẩm tốt', name: 'Khách', star: 5, verified: true, date: 1, photos: [], replies: [] }],
  },
  consultation: { needs: [{ code: 'lighting', label: 'Tư vấn chiếu sáng' }] },
  commitments: [{ name: 'Hỗ trợ kỹ thuật', image: '' }],
}

test('accepts safe PDP supplemental public data', () => assert.equal(isPublicProductExtrasPayload(good), true))
test('rejects unsafe video, policy, media and inconsistent review totals', () => {
  assert.equal(isPublicProductExtrasPayload({ ...good, videoEmbedUrl: 'https://evil.test/embed/abcdefghijk' }), false)
  assert.equal(isPublicProductExtrasPayload({ ...good, warranty: { ...good.warranty, policies: [{ label: 'x', href: 'https://evil.test/' }] } }), false)
  assert.equal(isPublicProductExtrasPayload({ ...good, reviews: { ...good.reviews, total: 2 } }), false)
  assert.equal(isPublicProductExtrasPayload({ ...good, commitments: [{ name: 'x', image: '/private/x.jpg' }] }), false)
})

test('review dates use a deterministic Vietnam timezone for SSR and browser hydration', () => {
  const reviews = readFileSync(new URL('../app/components/product-preview/ProductReviews.vue', import.meta.url), 'utf8')
  assert.match(reviews, /dateStyle: 'medium', timeZone: 'Asia\/Ho_Chi_Minh'/)
})
