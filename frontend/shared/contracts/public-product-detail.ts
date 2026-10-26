export interface PublicProductSpecification { key: string; label: string; value: string }
export interface PublicProductDetail {
  id: number; name: string; slug: string; url: string; code: string; summary: string
  profile: string; media: string[]; specifications: PublicProductSpecification[]
}
export interface PublicProductDetailPayload {
  schemaVersion: 1; locale: 'vi'; product: PublicProductDetail
  pricing: { included: false; reason: string }
  content: { rendered: false; reason: string }
  canonical: { url: string; sources: Record<string, string> }
  parity: Record<string, { included: false; reason: string }>
}

const slugPattern = /^[a-z0-9]+(?:-[a-z0-9]+)*$/
function record(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function text(value: unknown, allowEmpty = false): value is string { return typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[\u0000-\u001F\u007F]/.test(value) }
function id(value: unknown): value is number { return typeof value === 'number' && Number.isSafeInteger(value) && value > 0 }
function image(value: unknown): value is string { return text(value) && value.startsWith('/upload/product/') && !value.includes('..') && !/[<>"'`\s]/.test(value) }
function spec(value: unknown): value is PublicProductSpecification { return record(value) && text(value.key, true) && text(value.label) && text(value.value) }

export function isProductSlug(value: unknown): value is string { return typeof value === 'string' && slugPattern.test(value) }

export function isPublicProductDetailPayload(value: unknown): value is PublicProductDetailPayload {
  if (!record(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || !record(value.product) || !record(value.pricing) || value.pricing.included !== false || !record(value.content) || value.content.rendered !== false || !record(value.canonical) || !text(value.canonical.url) || !record(value.parity)) return false
  const product = value.product
  if (!id(product.id) || !text(product.name) || !isProductSlug(product.slug) || product.url !== `/san-pham/${product.slug}/` || !text(product.code, true) || !text(product.summary, true) || !/^[a-z0-9_]+$/.test(product.profile)) return false
  if (!Array.isArray(product.media) || !product.media.every(image) || !Array.isArray(product.specifications) || !product.specifications.every(spec)) return false
  if (!text(value.pricing.reason) || !text(value.content.reason)) return false
  return Object.values(value.parity).every((row) => record(row) && row.included === false && text(row.reason))
}
