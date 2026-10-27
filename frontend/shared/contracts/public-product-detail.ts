import { isPricing, type PublicCatalogPricing } from './public-catalog.ts'

export interface PublicProductSpecification { key: string; label: string; value: string }
export interface PublicProductDetail {
  id: number; name: string; slug: string; url: string; code: string; summary: string
  profile: string; media: string[]; specifications: PublicProductSpecification[]
}
export type PublicProductContentSource = '' | 'product' | 'brand' | 'cat' | 'list' | 'both' | 'stack' | 'hide' | 'own'
export type PublicProductContentBlock =
  | { type: 'heading'; text: string }
  | { type: 'text'; text: string }
  | { type: 'list'; ordered: boolean; items: string[] }
  | { type: 'image'; image: string; caption: string }
  | { type: 'media'; image: string; title: string; text: string; side: 'left' | 'right' | 'center' }
export interface PublicProductFaq { question: string; answer: string }
export type PublicProductDocumentType = 'datasheet' | 'manual' | 'warranty' | 'certificate' | 'other'
export interface PublicProductDocument { type: PublicProductDocumentType; title: string; url: string }
export interface PublicProductContent {
  rendered: true
  source: PublicProductContentSource
  blocks: PublicProductContentBlock[]
  faqs: PublicProductFaq[]
}
export interface PublicProductDetailPayload {
  schemaVersion: 1; locale: 'vi'; product: PublicProductDetail
  pricing: PublicCatalogPricing
  content: PublicProductContent
  documents: PublicProductDocument[]
  canonical: { url: string; sources: Record<string, string> }
  parity: Record<string, { included: false; reason: string }>
}

const slugPattern = /^[a-z0-9]+(?:-[a-z0-9]+)*$/
function record(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function text(value: unknown, allowEmpty = false): value is string { return typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[\u0000-\u001F\u007F]/.test(value) }
function id(value: unknown): value is number { return typeof value === 'number' && Number.isSafeInteger(value) && value > 0 }
function image(value: unknown): value is string { return text(value) && value.startsWith('/upload/product/') && !value.includes('..') && !/[<>"'`\s]/.test(value) }
function spec(value: unknown): value is PublicProductSpecification { return record(value) && text(value.key, true) && text(value.label) && text(value.value) }
function blockText(value: unknown, allowEmpty = false): value is string {
  return typeof value === 'string'
    && (allowEmpty || value.trim() !== '')
    && !/[\u0000-\u0008\u000B\u000C\u000E-\u001F\u007F]/.test(value)
}
function contentImage(value: unknown, allowEmpty = false): value is string {
  return value === '' ? allowEmpty : image(value)
}
function contentBlock(value: unknown): value is PublicProductContentBlock {
  if (!record(value) || typeof value.type !== 'string') return false
  if (value.type === 'heading' || value.type === 'text') return blockText(value.text)
  if (value.type === 'list') return typeof value.ordered === 'boolean'
    && Array.isArray(value.items) && value.items.length > 0 && value.items.length <= 50 && value.items.every((item) => blockText(item))
  if (value.type === 'image') return contentImage(value.image) && blockText(value.caption, true)
  if (value.type === 'media') return contentImage(value.image, true) && blockText(value.title, true) && blockText(value.text, true)
    && (value.image !== '' || value.title.trim() !== '' || value.text.trim() !== '')
    && ['left', 'right', 'center'].includes(value.side as string)
  return false
}
function content(value: unknown): value is PublicProductContent {
  if (!record(value) || value.rendered !== true || !['', 'product', 'brand', 'cat', 'list', 'both', 'stack', 'hide', 'own'].includes(value.source as string)) return false
  if (!Array.isArray(value.blocks) || value.blocks.length > 100 || !value.blocks.every(contentBlock)) return false
  return Array.isArray(value.faqs) && value.faqs.length <= 30
    && value.faqs.every((faq) => record(faq) && blockText(faq.question) && blockText(faq.answer))
}
function documentUrl(value: unknown): value is string {
  if (!text(value)) return false
  const ext = value.split(/[?#]/, 1)[0]?.match(/\.(pdf|docx?|xlsx?)$/i)?.[1]
  if (!ext) return false
  if (value.startsWith('/')) return (/^\/(?:upload|assets)\/[A-Za-z0-9._/-]+$/.test(value) && !value.includes('..'))
  try {
    const url = new URL(value)
    return url.protocol === 'https:' && !url.username && !url.password && !url.hash
  } catch { return false }
}
function document(value: unknown): value is PublicProductDocument {
  return record(value)
    && ['datasheet', 'manual', 'warranty', 'certificate', 'other'].includes(value.type as string)
    && text(value.title) && documentUrl(value.url)
}

export function isProductSlug(value: unknown): value is string { return typeof value === 'string' && slugPattern.test(value) }

export function isPublicProductDetailPayload(value: unknown): value is PublicProductDetailPayload {
  if (!record(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || !record(value.product) || !isPricing(value.pricing) || !content(value.content) || !Array.isArray(value.documents) || value.documents.length > 30 || !value.documents.every(document) || !record(value.canonical) || !text(value.canonical.url) || !record(value.parity)) return false
  const product = value.product
  if (!id(product.id) || !text(product.name) || !isProductSlug(product.slug) || product.url !== `/san-pham/${product.slug}` || !text(product.code, true) || !text(product.summary, true) || !text(product.profile) || !/^[a-z0-9_]+$/.test(product.profile)) return false
  if (!Array.isArray(product.media) || !product.media.every(image) || !Array.isArray(product.specifications) || !product.specifications.every(spec)) return false
  return Object.values(value.parity).every((row) => record(row) && row.included === false && text(row.reason))
}
