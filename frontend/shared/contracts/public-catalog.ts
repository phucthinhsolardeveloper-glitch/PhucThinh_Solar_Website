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
  pricing?: PublicCatalogPricing
}

export type PublicCatalogPricing =
  | { mode: 'price'; currency: 'VND'; regularPrice: number | null; salePrice: number | null }
  | { mode: 'quote'; currency: 'VND'; regularPrice: null; salePrice: null }

export interface PublicCatalogBrand { id: number; name: string }
export interface PublicCatalogPriceRange { key: '1' | '2' | '3'; label: string }
export interface PublicCatalogSubcategory { id: number; name: string; slug: string }
export type PublicCatalogSortKey = 'default' | 'abc' | 'abc-desc' | 'price' | 'price-desc' | 'new' | 'new-desc'

export interface PublicCatalogPayload {
  schemaVersion: 1
  locale: 'vi'
  category: [] | PublicCatalogCategory
  categories: PublicCatalogCategory[]
  products: PublicCatalogProduct[]
  pagination: { page: number; perPage: number; total: number }
  filters?: {
    brands: PublicCatalogBrand[]
    priceRanges: PublicCatalogPriceRange[]
    subcategories: PublicCatalogSubcategory[]
  }
  selected?: {
    brandIds: number[]
    priceKeys: string[]
    subcategoryIds: number[]
    sort: PublicCatalogSortKey
  }
  parity: {
    prices: { included: true } | { included: false; reason: string }
    filters: { included: true } | { included: false; reason: string }
    sort: { included: true } | { included: false; reason: string }
  }
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

function isSafeText(value: unknown): value is string {
  return typeof value === 'string' && value.trim() !== '' && !/[\u0000-\u001F\u007F]/.test(value)
}

function isSlug(value: unknown): value is string {
  return typeof value === 'string' && /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(value)
}

function isCategory(value: unknown): value is PublicCatalogCategory {
  return isRecord(value) && hasExactKeys(value, ['id', 'name', 'slug', 'url']) && isSafeId(value.id) && isSafeText(value.name) && isSlug(value.slug) && typeof value.url === 'string' && /^\/danh-muc-san-pham\/[a-z0-9]+(?:-[a-z0-9]+)*(?:\/[a-z0-9]+(?:-[a-z0-9]+)*){0,3}\/$/.test(value.url) && value.url.endsWith(`/${value.slug}/`)
}

export function isPublicCatalogProduct(value: unknown): value is PublicCatalogProduct {
  if (!isRecord(value)) return false
  const legacy = hasExactKeys(value, ['id', 'name', 'slug', 'url', 'media'])
  const extended = hasExactKeys(value, ['id', 'name', 'slug', 'url', 'media', 'powerWatts', 'pricing'])
  return (legacy || extended) && isSafeId(value.id) && isSafeText(value.name) && isSlug(value.slug) && value.url === `/san-pham/${value.slug}` && Array.isArray(value.media) && value.media.every((path) => typeof path === 'string' && /^\/upload\/product\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(path)) && (legacy || (value.powerWatts === null || typeof value.powerWatts === 'number' && Number.isFinite(value.powerWatts) && value.powerWatts > 0) && isPricing(value.pricing))
}

function isMoney(value: unknown): value is number | null { return value === null || typeof value === 'number' && Number.isSafeInteger(value) && value > 0 }
export function isPricing(value: unknown): value is PublicCatalogPricing {
  if (!isRecord(value) || !hasExactKeys(value, ['mode', 'currency', 'regularPrice', 'salePrice']) || value.currency !== 'VND') return false
  if (value.mode === 'quote') return value.regularPrice === null && value.salePrice === null
  return value.mode === 'price' && isMoney(value.regularPrice) && isMoney(value.salePrice) && (value.regularPrice !== null || value.salePrice !== null)
}

function isPriceParity(value: unknown): value is PublicCatalogPayload['parity']['prices'] {
  return isRecord(value) && (hasExactKeys(value, ['included']) && value.included === true || hasExactKeys(value, ['included', 'reason']) && value.included === false && (value.reason === 'public_pricing_disabled' || value.reason === 'public_pricing_authority_not_delegated'))
}

function isTogglableParity(value: unknown, legacyReason: string): value is { included: true } | { included: false; reason: string } {
  return isRecord(value) && (hasExactKeys(value, ['included']) && value.included === true || hasExactKeys(value, ['included', 'reason']) && value.included === false && value.reason === legacyReason)
}

function isBrand(value: unknown): value is PublicCatalogBrand {
  return isRecord(value) && hasExactKeys(value, ['id', 'name']) && isSafeId(value.id) && isSafeText(value.name)
}

function isPriceRange(value: unknown): value is PublicCatalogPriceRange {
  return isRecord(value) && hasExactKeys(value, ['key', 'label']) && typeof value.key === 'string' && /^[123]$/.test(value.key) && isSafeText(value.label)
}

function isSubcategory(value: unknown): value is PublicCatalogSubcategory {
  return isRecord(value) && hasExactKeys(value, ['id', 'name', 'slug']) && isSafeId(value.id) && isSafeText(value.name) && isSlug(value.slug)
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
  return isRecord(value) && hasExactKeys(value, ['brands', 'priceRanges', 'subcategories'])
    && Array.isArray(value.brands) && value.brands.every(isBrand)
    && Array.isArray(value.priceRanges) && value.priceRanges.every(isPriceRange)
    && Array.isArray(value.subcategories) && value.subcategories.every(isSubcategory)
}

function isCatalogSelected(value: unknown): value is NonNullable<PublicCatalogPayload['selected']> {
  return isRecord(value) && hasExactKeys(value, ['brandIds', 'priceKeys', 'subcategoryIds', 'sort'])
    && isIdArray(value.brandIds) && isPriceKeyArray(value.priceKeys) && isIdArray(value.subcategoryIds) && isSortKey(value.sort)
}

export function isPublicCatalogPayload(value: unknown): value is PublicCatalogPayload {
  if (!isRecord(value)) return false
  const legacy = hasExactKeys(value, ['schemaVersion', 'locale', 'category', 'categories', 'products', 'pagination', 'parity'])
  const extended = hasExactKeys(value, ['schemaVersion', 'locale', 'category', 'categories', 'products', 'pagination', 'filters', 'selected', 'parity'])
  if (!(legacy || extended) || value.schemaVersion !== 1 || value.locale !== 'vi') return false
  if (!(Array.isArray(value.category) && value.category.length === 0 || isCategory(value.category))) return false
  if (!Array.isArray(value.categories) || !value.categories.every(isCategory) || !Array.isArray(value.products) || !value.products.every(isPublicCatalogProduct)) return false
  if (!isRecord(value.pagination) || !hasExactKeys(value.pagination, ['page', 'perPage', 'total']) || typeof value.pagination.page !== 'number' || !Number.isSafeInteger(value.pagination.page) || value.pagination.page < 1 || value.pagination.page > 10000 || typeof value.pagination.perPage !== 'number' || !Number.isSafeInteger(value.pagination.perPage) || value.pagination.perPage < 1 || value.pagination.perPage > 48 || typeof value.pagination.total !== 'number' || !Number.isSafeInteger(value.pagination.total) || value.pagination.total < 0) return false
  if (extended && (!isCatalogFilters(value.filters) || !isCatalogSelected(value.selected))) return false
  if (!isRecord(value.parity) || !hasExactKeys(value.parity, ['prices', 'filters', 'sort'])) return false
  return isPriceParity(value.parity.prices) && isTogglableParity(value.parity.filters, 'legacy_filter_parity_not_verified') && isTogglableParity(value.parity.sort, 'legacy_sort_parity_not_verified')
}

export function isCatalogSlugChain(value: string): boolean {
  return value === '' || /^(?:[a-z0-9]+(?:-[a-z0-9]+)*){1}(?:\/[a-z0-9]+(?:-[a-z0-9]+)*){0,3}$/.test(value)
}
