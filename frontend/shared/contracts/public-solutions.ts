export interface PublicSolutionsLink { label: string; href: string }
export interface PublicSolutionsIntro { eyebrow: string; title: string; description: string; image: string; links: { consult: PublicSolutionsLink; explore: PublicSolutionsLink } }
export interface PublicSolutionCard { title: string; description: string; slug: string; url: string; image: string; actionLabel: string }
export interface PublicSolutionEquipment { name: string; description: string; url: string; image: string }
export interface PublicSolutionsCta { title: string; description: string; buttonLabel: string; image: string; links: { consult: PublicSolutionsLink }; hotline: { label: string; text: string; tel: string } | null }
export interface PublicSolutionsPayload {
  schemaVersion: 1; locale: 'vi'; path: '/giai-phap/'; title: string; intro: PublicSolutionsIntro | null
  cards: PublicSolutionCard[]; equipment: PublicSolutionEquipment[]; allProductsUrl: string
  labels: { choices: string; equipment: string; allProducts: string; empty: string; consult: string; phone: string }
  cta: PublicSolutionsCta | null; editorial: { rendered: false; reason: string }
}

function isRecord(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function isString(value: unknown): value is string { return typeof value === 'string' }
function isImagePath(value: unknown): value is string { return isString(value) && (value === '' || /^\/(?:upload|assets)\//.test(value)) }
function isPublicPath(value: unknown): value is string { return isString(value) && /^\/(?:giai-phap(?:\/[^/?#]+)?|san-pham|danh-muc-san-pham\/[^/?#]+)\/?(?:#[^?]*)?$/.test(value) }
function isLink(value: unknown): value is PublicSolutionsLink { return isRecord(value) && isString(value.label) && isString(value.href) && (value.href === '#ptsContactModal' || isPublicPath(value.href)) }

export function isPublicSolutionsPayload(value: unknown): value is PublicSolutionsPayload {
  if (!isRecord(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || value.path !== '/giai-phap/') return false
  if (!isString(value.title) || !isPublicPath(value.allProductsUrl)) return false
  const labels = value.labels
  if (!isRecord(labels) || !['choices', 'equipment', 'allProducts', 'empty', 'consult', 'phone'].every((key) => isString(labels[key]))) return false
  if (!Array.isArray(value.cards) || !value.cards.every((row) => isRecord(row) && isString(row.title) && isString(row.description) && isString(row.slug) && /^[-a-z0-9]+$/.test(row.slug) && row.url === `/giai-phap/${row.slug}/` && isImagePath(row.image) && isString(row.actionLabel))) return false
  if (!Array.isArray(value.equipment) || value.equipment.length > 4 || !value.equipment.every((row) => isRecord(row) && isString(row.name) && isString(row.description) && isPublicPath(row.url) && isImagePath(row.image))) return false
  if (!isRecord(value.editorial) || value.editorial.rendered !== false || !isString(value.editorial.reason)) return false
  if (value.intro !== null && (!isRecord(value.intro) || !isString(value.intro.eyebrow) || !isString(value.intro.title) || !isString(value.intro.description) || !isImagePath(value.intro.image) || !isRecord(value.intro.links) || !isLink(value.intro.links.consult) || !isLink(value.intro.links.explore))) return false
  if (value.cta !== null && (!isRecord(value.cta) || !isString(value.cta.title) || !isString(value.cta.description) || !isString(value.cta.buttonLabel) || !isImagePath(value.cta.image) || !isRecord(value.cta.links) || !isLink(value.cta.links.consult) || (value.cta.hotline !== null && (!isRecord(value.cta.hotline) || !isString(value.cta.hotline.label) || !isString(value.cta.hotline.text) || !isString(value.cta.hotline.tel))))) return false
  return true
}
