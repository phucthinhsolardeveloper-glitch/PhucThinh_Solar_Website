import { isPublicCatalogProduct, type PublicCatalogProduct } from './public-catalog.ts'

export interface PublicProductPolicyLink { label: string; href: string }
export interface PublicProductWarranty {
  summary: string
  override: string
  policies: PublicProductPolicyLink[]
}
export interface PublicProductReviewReply {
  name: string
  content: string
  isAdmin: boolean
  date: number
}
export interface PublicProductReviewVideo {
  url: string
  poster: string
}
export interface PublicProductReview {
  id: number
  title: string
  content: string
  name: string
  star: number
  verified: boolean
  date: number
  photos: string[]
  video: PublicProductReviewVideo | null
  replies: PublicProductReviewReply[]
  repliesTotal: number
}
export interface PublicProductReviews {
  total: number
  average: number
  counts: Record<'1' | '2' | '3' | '4' | '5', number>
  requireOrderCode: boolean
  offset: number
  hasMore: boolean
  items: PublicProductReview[]
}
export interface PublicProductReviewReplyPage {
  parentId: number
  offset: number
  total: number
  hasMore: boolean
  items: PublicProductReviewReply[]
}
export interface PublicProductNeed { code: string; label: string }
export interface PublicProductCommitment { name: string; image: string }
export interface PublicProductExtrasPayload {
  schemaVersion: 1
  locale: 'vi'
  productId: number
  profileKey: string
  csrfToken: string
  videoEmbedUrl: string
  warranty: PublicProductWarranty
  related: PublicCatalogProduct[]
  reviews: PublicProductReviews
  replyPage?: PublicProductReviewReplyPage
  consultation: { needs: PublicProductNeed[] }
  commitments: PublicProductCommitment[]
}

function record(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function id(value: unknown): value is number { return typeof value === 'number' && Number.isSafeInteger(value) && value > 0 }
function text(value: unknown, allowEmpty = false): value is string {
  return typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[\u0000-\u001F\u007F]/.test(value)
}
function count(value: unknown, max = Number.MAX_SAFE_INTEGER): value is number {
  return typeof value === 'number' && Number.isSafeInteger(value) && value >= 0 && value <= max
}
function photo(value: unknown, allowEmpty = false): value is string {
  return value === '' ? allowEmpty : typeof value === 'string' && /^\/upload\/photo\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(value)
}
function reviewVideo(value: unknown): value is PublicProductReviewVideo | null {
  return value === null || record(value) && typeof value.url === 'string'
    && /^\/upload\/video\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:mp4|mkv)$/i.test(value.url)
    && photo(value.poster, true)
}
function policy(value: unknown): value is PublicProductPolicyLink {
  return record(value) && text(value.label) && typeof value.href === 'string'
    && /^\/ho-tro\/[a-z0-9]+(?:-[a-z0-9]+)*\/$/.test(value.href)
}
function reply(value: unknown): value is PublicProductReviewReply {
  return record(value) && text(value.name, true) && text(value.content) && typeof value.isAdmin === 'boolean'
    && count(value.date)
}
function review(value: unknown): value is PublicProductReview {
  return record(value) && id(value.id) && text(value.title, true) && text(value.content) && text(value.name, true)
    && typeof value.star === 'number' && Number.isSafeInteger(value.star) && value.star >= 1 && value.star <= 5
    && typeof value.verified === 'boolean' && count(value.date)
    && Array.isArray(value.photos) && value.photos.length <= 3 && value.photos.every((item) => photo(item))
    && reviewVideo(value.video)
    && Array.isArray(value.replies) && value.replies.length <= 5 && value.replies.every(reply)
    && count(value.repliesTotal) && value.repliesTotal >= value.replies.length
}
function reviews(value: unknown): value is PublicProductReviews {
  if (!record(value) || !count(value.total) || typeof value.average !== 'number' || !Number.isFinite(value.average)
    || value.average < 0 || value.average > 5 || !record(value.counts) || !Array.isArray(value.items)
    || value.items.length > 10 || !value.items.every(review) || typeof value.requireOrderCode !== 'boolean'
    || !count(value.offset) || typeof value.hasMore !== 'boolean') return false
  const counts = value.counts
  const keys = ['1', '2', '3', '4', '5'] as const
  if (!keys.every((key) => count(counts[key]))) return false
  return keys.reduce((sum, key) => sum + Number(counts[key]), 0) === value.total
}
function replyPage(value: unknown): value is PublicProductReviewReplyPage {
  return record(value) && id(value.parentId) && count(value.offset) && count(value.total)
    && typeof value.hasMore === 'boolean' && Array.isArray(value.items) && value.items.length <= 5 && value.items.every(reply)
}
function need(value: unknown): value is PublicProductNeed {
  return record(value) && typeof value.code === 'string' && /^[a-z0-9_-]{1,64}$/.test(value.code) && text(value.label)
}
function commitment(value: unknown): value is PublicProductCommitment {
  return record(value) && text(value.name) && photo(value.image, true)
}
function warranty(value: unknown): value is PublicProductWarranty {
  return record(value) && text(value.summary, true) && text(value.override, true)
    && Array.isArray(value.policies) && value.policies.length <= 30 && value.policies.every(policy)
}

export function isPublicProductExtrasPayload(value: unknown): value is PublicProductExtrasPayload {
  if (!record(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || !id(value.productId)
    || typeof value.profileKey !== 'string' || !/^[a-z0-9_]+$/.test(value.profileKey)
    || typeof value.csrfToken !== 'string' || !/^[a-f0-9]{64}$/.test(value.csrfToken)
    || typeof value.videoEmbedUrl !== 'string'
    || value.videoEmbedUrl !== '' && !/^https:\/\/www\.youtube-nocookie\.com\/embed\/[A-Za-z0-9_-]{11}$/.test(value.videoEmbedUrl)
    || !warranty(value.warranty)
    || !Array.isArray(value.related) || value.related.length > 8 || !value.related.every(isPublicCatalogProduct)
    || !reviews(value.reviews)
    || value.replyPage !== undefined && !replyPage(value.replyPage)
    || !record(value.consultation) || !Array.isArray(value.consultation.needs) || value.consultation.needs.length > 50 || !value.consultation.needs.every(need)
    || !Array.isArray(value.commitments) || value.commitments.length > 8 || !value.commitments.every(commitment)) return false
  return true
}
