import { isPublicSanitizedRichText, type PublicSanitizedRichText } from './editorial-rich-text.ts'

export interface PublicSolutionsLink { label: string; href: string }
export interface PublicSolutionsIntro { eyebrow: string; title: string; description: string; image: string; links: { consult: PublicSolutionsLink; explore: PublicSolutionsLink } }
export interface PublicSolutionCard { title: string; description: string; slug: string; url: string; image: string; actionLabel: string }
export interface PublicSolutionEquipment { name: string; description: string; url: string; image: string }
export interface PublicSolutionsCta { title: string; description: string; buttonLabel: string; image: string; links: { consult: PublicSolutionsLink }; hotline: { label: string; text: string; tel: string } | null }
export interface PublicSolutionsPayload {
  schemaVersion: 1; locale: 'vi'; path: '/giai-phap/'; title: string; intro: PublicSolutionsIntro | null
  cards: PublicSolutionCard[]; equipment: PublicSolutionEquipment[]; allProductsUrl: string
  labels: { choices: string; equipment: string; allProducts: string; empty: string; consult: string; phone: string }
  cta: PublicSolutionsCta | null
  editorial: { content: PublicSanitizedRichText; toc: { rendered: true; items: Array<{ id: string; label: string; level: 2 | 3 }> } | { rendered: false; reason: string } }
}
export interface PublicSolutionCategoryPayload {
  schemaVersion: 1; entity: 'solution-category'; slug: string; canonical: string
  category: { id: number; slug: string; name: string; description: string; image: string; type: 'giai-phap'; level: 'list' | 'cat' | 'item' | 'sub'; path: string }
  title: string; description: string; cards: PublicSolutionCard[]; pagination: { page: number; perPage: 12; total: number }; cta: PublicSolutionsCta | null
  labels: { choices: string; choice: string; empty: string; consult: string }
}

function isRecord(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function isString(value: unknown): value is string { return typeof value === 'string' }
function isImagePath(value: unknown): value is string { return isString(value) && (value === '' || /^\/(?:upload|assets)\//.test(value)) }
function isPublicPath(value: unknown): value is string { return isString(value) && /^\/(?:giai-phap(?:\/[^/?#]+)?|san-pham|danh-muc-san-pham\/[^/?#]+)\/?(?:#[^?]*)?$/.test(value) }
function isLink(value: unknown): value is PublicSolutionsLink { return isRecord(value) && isString(value.label) && isString(value.href) && (value.href === '#ptsContactModal' || isPublicPath(value.href)) }
function isToc(value: unknown): value is PublicSolutionsPayload['editorial']['toc'] {
  if (!isRecord(value) || typeof value.rendered !== 'boolean') return false
  if (value.rendered === false) return isString(value.reason) && ['no_editorial_content', 'no_editorial_headings'].includes(value.reason)
  return Array.isArray(value.items) && value.items.length > 0 && value.items.length <= 30
    && value.items.every((item) => isRecord(item) && typeof item.id === 'string' && /^solution-section-[1-9][0-9]*$/.test(item.id)
      && isString(item.label) && item.label.trim() !== '' && (item.level === 2 || item.level === 3))
}

export function isPublicSolutionsPayload(value: unknown): value is PublicSolutionsPayload {
  if (!isRecord(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || value.path !== '/giai-phap/') return false
  if (!isString(value.title) || !isPublicPath(value.allProductsUrl)) return false
  const labels = value.labels
  if (!isRecord(labels) || !['choices', 'equipment', 'allProducts', 'empty', 'consult', 'phone'].every((key) => isString(labels[key]))) return false
  if (!Array.isArray(value.cards) || !value.cards.every((row) => isRecord(row) && isString(row.title) && isString(row.description) && isString(row.slug) && /^[-a-z0-9]+$/.test(row.slug) && row.url === `/giai-phap/${row.slug}/` && isImagePath(row.image) && isString(row.actionLabel))) return false
  if (!Array.isArray(value.equipment) || value.equipment.length > 4 || !value.equipment.every((row) => isRecord(row) && isString(row.name) && isString(row.description) && isPublicPath(row.url) && isImagePath(row.image))) return false
  if (!isRecord(value.editorial) || !isPublicSanitizedRichText(value.editorial.content) || !isToc(value.editorial.toc)) return false
  if (value.intro !== null && (!isRecord(value.intro) || !isString(value.intro.eyebrow) || !isString(value.intro.title) || !isString(value.intro.description) || !isImagePath(value.intro.image) || !isRecord(value.intro.links) || !isLink(value.intro.links.consult) || !isLink(value.intro.links.explore))) return false
  if (value.cta !== null && (!isRecord(value.cta) || !isString(value.cta.title) || !isString(value.cta.description) || !isString(value.cta.buttonLabel) || !isImagePath(value.cta.image) || !isRecord(value.cta.links) || !isLink(value.cta.links.consult) || (value.cta.hotline !== null && (!isRecord(value.cta.hotline) || !isString(value.cta.hotline.label) || !isString(value.cta.hotline.text) || !isString(value.cta.hotline.tel))))) return false
  return true
}

export function isPublicSolutionCategoryPayload(value: unknown): value is PublicSolutionCategoryPayload {
  if (!isRecord(value) || value.schemaVersion !== 1 || value.entity !== 'solution-category' || typeof value.slug !== 'string' || !/^[-a-z0-9]+$/.test(value.slug) || value.canonical !== `/${value.slug}/`) return false
  const category = value.category
  if (!isRecord(category) || !Number.isSafeInteger(category.id) || Number(category.id) <= 0 || category.slug !== value.slug || category.type !== 'giai-phap' || !['list', 'cat', 'item', 'sub'].includes(String(category.level)) || typeof category.path !== 'string' || category.path.split('/').at(-1) !== category.slug || !category.path.split('/').every((part) => /^[-a-z0-9]+$/.test(part)) || typeof category.name !== 'string' || typeof category.description !== 'string' || !isImagePath(category.image)) return false
  if (category.path.split('/').length !== ({ list: 1, cat: 2, item: 3, sub: 4 } as Record<string, number>)[String(category.level)]) return false
  if (typeof value.title !== 'string' || typeof value.description !== 'string' || !Array.isArray(value.cards) || !value.cards.every((row) => isRecord(row) && typeof row.title === 'string' && typeof row.description === 'string' && typeof row.slug === 'string' && /^[-a-z0-9]+$/.test(row.slug) && row.url === `/giai-phap/${row.slug}/` && isImagePath(row.image) && typeof row.actionLabel === 'string')) return false
  if (!isRecord(value.pagination) || typeof value.pagination.page !== 'number' || !Number.isSafeInteger(value.pagination.page) || value.pagination.page < 1 || value.pagination.perPage !== 12 || typeof value.pagination.total !== 'number' || !Number.isSafeInteger(value.pagination.total) || value.pagination.total < 0) return false
  if (value.cta !== null && (!isRecord(value.cta) || typeof value.cta.title !== 'string' || typeof value.cta.description !== 'string' || typeof value.cta.buttonLabel !== 'string' || !isImagePath(value.cta.image) || !isRecord(value.cta.links) || !isLink(value.cta.links.consult) || (value.cta.hotline !== null && (!isRecord(value.cta.hotline) || typeof value.cta.hotline.label !== 'string' || typeof value.cta.hotline.text !== 'string' || typeof value.cta.hotline.tel !== 'string')))) return false
  const labels = value.labels
  return isRecord(labels) && ['choices', 'choice', 'empty', 'consult'].every((key) => typeof labels[key] === 'string')
}
