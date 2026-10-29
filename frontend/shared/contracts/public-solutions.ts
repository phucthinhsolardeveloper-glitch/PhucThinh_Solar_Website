import { isPublicSanitizedRichText, type PublicSanitizedRichText } from './editorial-rich-text.ts'

export interface PublicSolutionsLink { label: string; href: string }
export interface PublicSolutionsIntro { eyebrow: string; title: string; description: string; image: string; links: { consult: PublicSolutionsLink; explore: PublicSolutionsLink } }
export interface PublicSolutionCard { title: string; description: string; slug: string; url: string; image: string; actionLabel: string }
export interface PublicSolutionCategory { id: number; name: string; description: string; slug: string; canonicalPath: string; cards: PublicSolutionCard[]; navigation: Array<{ id: number; name: string; slug: string; url: string; active: boolean }> }
export interface PublicSolutionEquipment { name: string; description: string; url: string; image: string }
export interface PublicSolutionsCta { title: string; description: string; buttonLabel: string; image: string; links: { consult: PublicSolutionsLink }; hotline: { label: string; text: string; tel: string } | null }
export interface PublicSolutionsPayload {
  schemaVersion: 1; locale: 'vi'; path: '/giai-phap/'; title: string; intro: PublicSolutionsIntro | null
  category: PublicSolutionCategory | null; cards: PublicSolutionCard[]; equipment: PublicSolutionEquipment[]; allProductsUrl: string
  labels: { choices: string; equipment: string; allProducts: string; empty: string; consult: string; phone: string }
  cta: PublicSolutionsCta | null
  editorial: { content: PublicSanitizedRichText; toc: { rendered: true; items: Array<{ id: string; label: string; level: 2 | 3 }> } | { rendered: false; reason: string } }
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

function isSolutionCard(row: unknown): row is PublicSolutionCard {
  return isRecord(row) && isString(row.title) && isString(row.description) && isString(row.slug)
    && /^[-a-z0-9]+$/.test(row.slug) && row.url === `/giai-phap/${row.slug}/`
    && isImagePath(row.image) && isString(row.actionLabel)
}

function isSolutionCategory(value: unknown): value is PublicSolutionCategory {
  return isRecord(value) && typeof value.id === 'number' && Number.isSafeInteger(value.id) && value.id > 0
    && isString(value.name) && value.name !== '' && isString(value.description)
    && isString(value.slug) && /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(value.slug)
    && value.canonicalPath === `/${value.slug}`
    && Array.isArray(value.cards) && value.cards.length > 0 && value.cards.every(isSolutionCard)
    && Array.isArray(value.navigation) && value.navigation.every((row) => isRecord(row)
      && typeof row.id === 'number' && Number.isSafeInteger(row.id) && row.id > 0
      && isString(row.name) && row.name !== '' && isString(row.slug) && /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(row.slug)
      && (typeof row.url === 'string' && /^\/(?:giai-phap|danh-muc-giai-phap)\/[a-z0-9]+(?:-[a-z0-9]+)*\/$/.test(row.url))
      && typeof row.active === 'boolean')
}

export function isPublicSolutionsPayload(value: unknown): value is PublicSolutionsPayload {
  if (!isRecord(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || value.path !== '/giai-phap/') return false
  if (!isString(value.title) || !isPublicPath(value.allProductsUrl)) return false
  if (value.category !== null && !isSolutionCategory(value.category)) return false
  const labels = value.labels
  if (!isRecord(labels) || !['choices', 'equipment', 'allProducts', 'empty', 'consult', 'phone'].every((key) => isString(labels[key]))) return false
  if (!Array.isArray(value.cards) || !value.cards.every(isSolutionCard)) return false
  if (!Array.isArray(value.equipment) || value.equipment.length > 4 || !value.equipment.every((row) => isRecord(row) && isString(row.name) && isString(row.description) && isPublicPath(row.url) && isImagePath(row.image))) return false
  if (!isRecord(value.editorial) || !isPublicSanitizedRichText(value.editorial.content) || !isToc(value.editorial.toc)) return false
  if (value.intro !== null && (!isRecord(value.intro) || !isString(value.intro.eyebrow) || !isString(value.intro.title) || !isString(value.intro.description) || !isImagePath(value.intro.image) || !isRecord(value.intro.links) || !isLink(value.intro.links.consult) || !isLink(value.intro.links.explore))) return false
  if (value.cta !== null && (!isRecord(value.cta) || !isString(value.cta.title) || !isString(value.cta.description) || !isString(value.cta.buttonLabel) || !isImagePath(value.cta.image) || !isRecord(value.cta.links) || !isLink(value.cta.links.consult) || (value.cta.hotline !== null && (!isRecord(value.cta.hotline) || !isString(value.cta.hotline.label) || !isString(value.cta.hotline.text) || !isString(value.cta.hotline.tel))))) return false
  return true
}
