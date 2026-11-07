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
    requireOrderCode: false,
    offset: 0,
    hasMore: false,
    items: [{ id: 1, title: 'Tốt', content: 'Sản phẩm tốt', name: 'Khách', star: 5, verified: true, date: 1, photos: [], video: null, replies: [], repliesTotal: 0 }],
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

test('review media and paged parent/reply contracts stay bounded and same-origin', () => {
  const mediaReview = { ...good.reviews.items[0], photos: ['/upload/photo/review.jpg'], video: { url: '/upload/video/review.mp4', poster: '/upload/photo/poster.jpg' }, replies: [{ name: 'Admin', content: 'Phản hồi', isAdmin: true, date: 2 }], repliesTotal: 2 }
  assert.equal(isPublicProductExtrasPayload({ ...good, reviews: { ...good.reviews, total: 1, items: [mediaReview] } }), true)
  assert.equal(isPublicProductExtrasPayload({ ...good, reviews: { ...good.reviews, items: [{ ...mediaReview, video: { url: 'https://evil.test/review.mp4', poster: '' } }] } }), false)
  assert.equal(isPublicProductExtrasPayload({ ...good, replyPage: { parentId: 1, offset: 1, total: 2, hasMore: false, items: [{ name: 'Admin', content: 'Tiếp theo', isAdmin: true, date: 3 }] } }), true)

  const extrasApi = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/api/pts_v1_product_extras.php', import.meta.url), 'utf8')
  const reviewApi = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/api/pts_v1_review.php', import.meta.url), 'utf8')
  const reviewUi = readFileSync(new URL('../app/components/product-preview/ProductReviews.vue', import.meta.url), 'utf8')
  assert.match(extrasApi, /reviewOffset/)
  assert.match(extrasApi, /replyParent/)
  assert.match(extrasApi, /ptsPdpExtrasReviewVideo/)
  assert.match(extrasApi, /requireOrderCode/)
  assert.match(reviewApi, /multipart\/form-data/)
  assert.match(reviewApi, /Comments\(\$d,\$func\)/)
  assert.match(reviewUi, /review-file-photo\[\]/)
  assert.match(reviewUi, /review-file-video/)
  assert.match(reviewUi, /credentials: 'same-origin'/)
  assert.match(reviewUi, /Tải thêm nhận xét/)
  assert.match(reviewUi, /Xem thêm phản hồi/)
  assert.match(reviewUi, /reviewVideoSource\(review\.video\.url\)/)
  assert.doesNotMatch(reviewUi, /phpPublicOrigin/)
  assert.match(reviewUi, /:poster="review\.video\.poster \|\| undefined"/)
})
