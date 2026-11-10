export interface PublicCatalogCategory {
  id: number
  name: string
  slug: string
  url: string
}

export interface PublicCatalogProduct {
  id: number
  name: string
  slug: string
  url: string
  media: string[]
  powerWatts?: number | null
  quickSpecs?: string[]
  pricing?: PublicCatalogPricing
  /** Transparent promotion frame laid over the picture while a campaign covers the product. */
  promoFrame?: string
}

export type PublicCatalogPricing =
  | { mode: 'price'; currency: 'VND'; regularPrice: number | null; salePrice: number | null }
  | { mode: 'quote'; currency: 'VND'; regularPrice: null; salePrice: null }

export interface PublicCatalogBrand { id: number; name: string; logo?: string }
export interface PublicCatalogPriceRange { key: '1' | '2' | '3'; label: string }
export interface PublicCatalogSubcategory { id: number; name: string; slug: string }
export interface PublicCatalogNeed { key: 'du-an-cong-trinh' | 'chieu-sang-gia-dinh' | 'san-vuon-canh-quan'; label: string }
export interface PublicCatalogHero { image: string; alt: string }
export interface PublicCatalogFaq { question: string; answer: string }
export interface PublicCatalogApplication { title: string; description: string; image: string; href: string }
export interface PublicCatalogSupport { title: string; description: string; image: string; href: string }
export type PublicCatalogSortKey = 'default' | 'abc' | 'abc-desc' | 'price' | 'price-desc' | 'new' | 'new-desc'

export interface PublicCatalogPayload {
  schemaVersion: 1
  locale: 'vi'
  seo: { title: string; description: string; keywords: string; image?: string }
  canonical: { url: string }
  category: [] | PublicCatalogCategory
  /** Parent categories above the current one (top first); empty on the product landing. */
  breadcrumbs?: Array<{ label: string; url: string }>
  categories: PublicCatalogCategory[]
  products: PublicCatalogProduct[]
  pagination: { page: number; perPage: number; total: number }
  filters?: {
    brands: PublicCatalogBrand[]
    priceRanges: PublicCatalogPriceRange[]
    subcategories: PublicCatalogSubcategory[]
    needs?: PublicCatalogNeed[]
    partners?: Array<{ id: number; name: string; logo: string }>
  }
  selected?: {
    brandIds: number[]
    priceKeys: string[]
    subcategoryIds: number[]
    sort: PublicCatalogSortKey
    needKeys?: PublicCatalogNeed['key'][]
  }
  categoryHero: PublicCatalogHero | null
  categoryContent: { title: string; applications: PublicCatalogApplication[]; faq: PublicCatalogFaq[] }
  support: PublicCatalogSupport | null
  pricesVisible: boolean
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return value !== null && typeof value === 'object' && !Array.isArray(value)
}

function hasExactKeys(value: Record<string, unknown>, keys: string[]) {
  const actual = Object.keys(value).sort()
  return actual.length === keys.length && actual.every((key, index) => key === [...keys].sort()[index])
}

function isSafeId(value: unknown): value is number {
  return typeof value === 'number' && Number.isSafeInteger(value) && value > 0
}

function isSafeText(value: unknown, allowEmpty = false): value is string {
  return typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[\u0000-\u001F\u007F]/.test(value)
}

function isSlug(value: unknown): value is string {
  return typeof value === 'string' && /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(value)
}

function isPublicCatalogVisual(value: unknown): value is string {
  return typeof value === 'string' && (value === '' || /^\/(?:upload\/(?:photo|product|seopage)|assets\/images\/site-visuals)\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(value))
}

function isCategoryContent(value: unknown): value is PublicCatalogPayload['categoryContent'] {
  return isRecord(value) && hasExactKeys(value, ['title', 'applications', 'faq']) && (value.title === '' || isSafeText(value.title))
    && Array.isArray(value.applications) && value.applications.every((row) => isRecord(row) && hasExactKeys(row, ['title', 'description', 'image', 'href']) && isSafeText(row.title) && isSafeText(row.description, true) && isPublicCatalogVisual(row.image) && typeof row.href === 'string' && (row.href.startsWith('/') || row.href === ''))
    && Array.isArray(value.faq) && value.faq.every((row) => isRecord(row) && hasExactKeys(row, ['question', 'answer']) && isSafeText(row.question) && isSafeText(row.answer))
}

function isCategory(value: unknown): value is PublicCatalogCategory {
  return isRecord(value) && hasExactKeys(value, ['id', 'name', 'slug', 'url']) && isSafeId(value.id) && isSafeText(value.name) && isSlug(value.slug) && typeof value.url === 'string' && /^\/danh-muc-san-pham\/[a-z0-9]+(?:-[a-z0-9]+)*(?:\/[a-z0-9]+(?:-[a-z0-9]+)*){0,3}\/$/.test(value.url) && value.url.endsWith(`/${value.slug}/`)
}

const PROMO_FRAME = /^\/upload\/photo\/[A-Za-z0-9][A-Za-z0-9._-]*\.png$/i

export function isPublicCatalogProduct(value: unknown): value is PublicCatalogProduct {
  if (!isRecord(value)) return false
  if ('promoFrame' in value) {
    const { promoFrame, ...card } = value
    return typeof promoFrame === 'string' && PROMO_FRAME.test(promoFrame) && isPublicCatalogProduct(card)
  }
  const basic = hasExactKeys(value, ['id', 'name', 'slug', 'url', 'media'])
  const extended = hasExactKeys(value, ['id', 'name', 'slug', 'url', 'media', 'powerWatts', 'pricing'])
  const extendedWithSpecs = hasExactKeys(value, ['id', 'name', 'slug', 'url', 'media', 'powerWatts', 'quickSpecs', 'pricing'])
  const baseIsValid = (basic || extended || extendedWithSpecs) && isSafeId(value.id) && isSafeText(value.name) && isSlug(value.slug) && value.url === `/san-pham/${value.slug}` && Array.isArray(value.media) && value.media.every((path) => typeof path === 'string' && /^\/upload\/product\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(path))
  if (!baseIsValid || basic) return baseIsValid
  const pricingIsValid = (value.powerWatts === null || typeof value.powerWatts === 'number' && Number.isFinite(value.powerWatts) && value.powerWatts > 0) && isPricing(value.pricing)
  if (!pricingIsValid) return false
  // CMS technical fields can legitimately contain comparison signs (for example
  // "> 12 tiếng"). Vue renders these with text interpolation, so allow the
  // symbols while still rejecting tag-shaped markup in the public DTO.
  return !extendedWithSpecs || Array.isArray(value.quickSpecs) && value.quickSpecs.length <= 3 && value.quickSpecs.every((spec) => isSafeText(spec) && spec.length <= 100 && !/<\s*\/?\s*[a-z][^>]*>/i.test(spec))
}

function isMoney(value: unknown): value is number | null { return value === null || typeof value === 'number' && Number.isSafeInteger(value) && value > 0 }
export function isPricing(value: unknown): value is PublicCatalogPricing {
  if (!isRecord(value) || !hasExactKeys(value, ['mode', 'currency', 'regularPrice', 'salePrice']) || value.currency !== 'VND') return false
  if (value.mode === 'quote') return value.regularPrice === null && value.salePrice === null
  return value.mode === 'price' && isMoney(value.regularPrice) && isMoney(value.salePrice) && (value.regularPrice !== null || value.salePrice !== null)
}

export function isBrandLogo(value: unknown): value is string {
  return value === '' || typeof value === 'string' && /^\/upload\/product\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:png|jpe?g|webp|svg)$/i.test(value)
}

function isBrand(value: unknown): value is PublicCatalogBrand {
  return isRecord(value) && (hasExactKeys(value, ['id', 'name']) || hasExactKeys(value, ['id', 'name', 'logo']) && isBrandLogo(value.logo)) && isSafeId(value.id) && isSafeText(value.name)
}

function isPriceRange(value: unknown): value is PublicCatalogPriceRange {
  return isRecord(value) && hasExactKeys(value, ['key', 'label']) && typeof value.key === 'string' && /^[123]$/.test(value.key) && isSafeText(value.label)
}

function isSubcategory(value: unknown): value is PublicCatalogSubcategory {
  return isRecord(value) && hasExactKeys(value, ['id', 'name', 'slug']) && isSafeId(value.id) && isSafeText(value.name) && isSlug(value.slug)
}

const catalogNeedKeys: PublicCatalogNeed['key'][] = ['du-an-cong-trinh', 'chieu-sang-gia-dinh', 'san-vuon-canh-quan']
function isNeed(value: unknown): value is PublicCatalogNeed {
  return isRecord(value) && hasExactKeys(value, ['key', 'label'])
    && typeof value.key === 'string' && (catalogNeedKeys as string[]).includes(value.key) && isSafeText(value.label)
}

const catalogSortKeys: PublicCatalogSortKey[] = ['default', 'abc', 'abc-desc', 'price', 'price-desc', 'new', 'new-desc']
function isSortKey(value: unknown): value is PublicCatalogSortKey {
  return typeof value === 'string' && (catalogSortKeys as string[]).includes(value)
}

function isIdArray(value: unknown): value is number[] {
  return Array.isArray(value) && value.every(isSafeId)
}

function isPriceKeyArray(value: unknown): value is string[] {
  return Array.isArray(value) && value.every((item) => typeof item === 'string' && /^[123]$/.test(item))
}

function isCatalogFilters(value: unknown): value is NonNullable<PublicCatalogPayload['filters']> {
  if (!isRecord(value)) return false
  if (Object.prototype.hasOwnProperty.call(value, 'partners')) {
    const { partners, ...rest } = value
    return Array.isArray(partners) && partners.every((item) => isRecord(item) && hasExactKeys(item, ['id', 'name', 'logo']) && isSafeId(item.id) && isSafeText(item.name) && item.logo !== '' && isBrandLogo(item.logo))
      && isCatalogFilters(rest)
  }
  const hasNeeds = Object.prototype.hasOwnProperty.call(value, 'needs')
  return (hasExactKeys(value, ['brands', 'priceRanges', 'subcategories']) || hasNeeds && hasExactKeys(value, ['brands', 'priceRanges', 'subcategories', 'needs']))
    && Array.isArray(value.brands) && value.brands.every(isBrand)
    && Array.isArray(value.priceRanges) && value.priceRanges.every(isPriceRange)
    && Array.isArray(value.subcategories) && value.subcategories.every(isSubcategory)
    && (!hasNeeds || Array.isArray(value.needs) && value.needs.every(isNeed))
}

function isCatalogSelected(value: unknown): value is NonNullable<PublicCatalogPayload['selected']> {
  if (!isRecord(value)) return false
  const hasNeedKeys = Object.prototype.hasOwnProperty.call(value, 'needKeys')
  return (hasExactKeys(value, ['brandIds', 'priceKeys', 'subcategoryIds', 'sort']) || hasNeedKeys && hasExactKeys(value, ['brandIds', 'priceKeys', 'subcategoryIds', 'sort', 'needKeys']))
    && isIdArray(value.brandIds) && isPriceKeyArray(value.priceKeys) && isIdArray(value.subcategoryIds) && isSortKey(value.sort)
    && (!hasNeedKeys || Array.isArray(value.needKeys) && value.needKeys.every((key) => typeof key === 'string' && (catalogNeedKeys as string[]).includes(key)))
}

export function isPublicCatalogPayload(value: unknown): value is PublicCatalogPayload {
  if (!isRecord(value)) return false
  if ('breadcrumbs' in value) {
    const { breadcrumbs, ...rest } = value
    if (!Array.isArray(breadcrumbs) || !breadcrumbs.every((crumb) => isRecord(crumb) && hasExactKeys(crumb, ['label', 'url']) && isSafeText(crumb.label) && typeof crumb.url === 'string' && /^\/[a-z0-9/-]*$/.test(crumb.url))) return false
    return isPublicCatalogPayload(rest)
  }
  const baseKeys = ['schemaVersion', 'locale', 'seo', 'canonical', 'category', 'categories', 'products', 'pagination', 'pricesVisible']
  const basic = hasExactKeys(value, baseKeys)
  const extended = hasExactKeys(value, [...baseKeys, 'filters', 'selected', 'categoryHero', 'categoryContent', 'support'])
  if (!(basic || extended) || value.schemaVersion !== 1 || value.locale !== 'vi') return false
  if (!(Array.isArray(value.category) && value.category.length === 0 || isCategory(value.category))) return false
  if (!isRecord(value.seo) || !(hasExactKeys(value.seo, ['title', 'description', 'keywords']) || hasExactKeys(value.seo, ['title', 'description', 'keywords', 'image'])) || !isSafeText(value.seo.title) || !isSafeText(value.seo.description, true) || !isSafeText(value.seo.keywords, true) || /[<>]/.test(value.seo.title) || /[<>]/.test(value.seo.description) || /[<>]/.test(value.seo.keywords) || value.seo.image !== undefined && !isPublicCatalogVisual(value.seo.image)) return false
  const expectedCanonical = Array.isArray(value.category) ? '/san-pham/' : value.category.url
  if (!isRecord(value.canonical) || !hasExactKeys(value.canonical, ['url']) || value.canonical.url !== expectedCanonical) return false
  if (!Array.isArray(value.categories) || !value.categories.every(isCategory) || !Array.isArray(value.products) || !value.products.every(isPublicCatalogProduct)) return false
  if (!isRecord(value.pagination) || !hasExactKeys(value.pagination, ['page', 'perPage', 'total']) || typeof value.pagination.page !== 'number' || !Number.isSafeInteger(value.pagination.page) || value.pagination.page < 1 || value.pagination.page > 10000 || typeof value.pagination.perPage !== 'number' || !Number.isSafeInteger(value.pagination.perPage) || value.pagination.perPage < 1 || value.pagination.perPage > 48 || typeof value.pagination.total !== 'number' || !Number.isSafeInteger(value.pagination.total) || value.pagination.total < 0) return false
  if (extended && (!isCatalogFilters(value.filters) || !isCatalogSelected(value.selected))) return false
  if (extended && (!(value.categoryHero === null || isRecord(value.categoryHero) && hasExactKeys(value.categoryHero, ['image', 'alt']) && isPublicCatalogVisual(value.categoryHero.image) && isSafeText(value.categoryHero.alt, true)) || !isCategoryContent(value.categoryContent) || !(value.support === null || isRecord(value.support) && hasExactKeys(value.support, ['title', 'description', 'image', 'href']) && isSafeText(value.support.title) && isSafeText(value.support.description, true) && isPublicCatalogVisual(value.support.image) && typeof value.support.href === 'string' && (value.support.href === '' || value.support.href.startsWith('/'))))) return false
  return typeof value.pricesVisible === 'boolean'
}

export function isCatalogSlugChain(value: string): boolean {
  return value === '' || /^(?:[a-z0-9]+(?:-[a-z0-9]+)*){1}(?:\/[a-z0-9]+(?:-[a-z0-9]+)*){0,3}$/.test(value)
}
