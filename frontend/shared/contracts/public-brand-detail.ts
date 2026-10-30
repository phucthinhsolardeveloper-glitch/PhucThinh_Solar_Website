import { isPublicCatalogProduct, type PublicCatalogProduct, type PublicCatalogSortKey } from './public-catalog.ts'
import { isPublicImage } from './core.ts'

export interface PublicBrandDetailBrand { id: number; name: string; slug: string; url: string }
export interface PublicBrandDetailSeo { title: string; description: string; keywords: string; image: string }
export interface PublicBrandDetailFilters {
  brands: PublicBrandDetailBrand[]
  priceRanges: Array<{ key: '1' | '2' | '3'; label: string }>
}
export interface PublicBrandDetailPayload {
  schemaVersion: 1
  locale: 'vi'
  path: string
  brand: PublicBrandDetailBrand
  seo?: PublicBrandDetailSeo
  products: PublicCatalogProduct[]
  pagination: { page: number; perPage: number; total: number }
  filters?: PublicBrandDetailFilters
  selected: { sort: PublicCatalogSortKey; priceKeys?: Array<'1' | '2' | '3'> }
  parity: {
    prices: { included: true } | { included: false; reason: string }
    seoOverrides: { included: true } | { included: false; reason: 'legacy_seo_override_table_not_delegated' }
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
function isSafeText(value: unknown, allowEmpty = false): value is string {
  return typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[\u0000-\u001F\u007F]/.test(value)
}
function isSlug(value: unknown): value is string {
  return typeof value === 'string' && /^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(value)
}

function isBrand(value: unknown): value is PublicBrandDetailBrand {
  return isRecord(value) && hasExactKeys(value, ['id', 'name', 'slug', 'url'])
    && isSafeId(value.id) && isSafeText(value.name) && isSlug(value.slug)
    && typeof value.url === 'string' && value.url === `/thuong-hieu/${value.slug}/`
}

function isBrandSeo(value: unknown): value is PublicBrandDetailSeo {
  return isRecord(value) && hasExactKeys(value, ['title', 'description', 'keywords', 'image'])
    && isSafeText(value.title) && isSafeText(value.description, true) && isSafeText(value.keywords, true)
    && !/[<>]/.test(value.title) && !/[<>]/.test(value.description) && !/[<>]/.test(value.keywords)
    && isPublicImage(value.image, ['/upload/product/'])
}

function isBrandFilters(value: unknown): value is PublicBrandDetailFilters {
  return isRecord(value) && hasExactKeys(value, ['brands', 'priceRanges'])
    && Array.isArray(value.brands) && value.brands.every(isBrand)
    && Array.isArray(value.priceRanges) && value.priceRanges.every((range) => isRecord(range)
      && hasExactKeys(range, ['key', 'label']) && ['1', '2', '3'].includes(range.key as string) && isSafeText(range.label))
}

function isPriceParity(value: unknown): value is PublicBrandDetailPayload['parity']['prices'] {
  return isRecord(value) && (hasExactKeys(value, ['included']) && value.included === true
    || hasExactKeys(value, ['included', 'reason']) && value.included === false && value.reason === 'public_pricing_disabled')
}

export function isPublicBrandDetailPayload(value: unknown): value is PublicBrandDetailPayload {
  if (!isRecord(value)) return false
  const hasFilters = Object.prototype.hasOwnProperty.call(value, 'filters')
  const hasSeo = Object.prototype.hasOwnProperty.call(value, 'seo')
  const keys = ['schemaVersion', 'locale', 'path', 'brand', 'products', 'pagination', 'selected', 'parity']
  if (hasFilters) keys.push('filters')
  if (hasSeo) keys.push('seo')
  if (!hasExactKeys(value, keys)) return false
  if (value.schemaVersion !== 1 || value.locale !== 'vi') return false
  if (!isBrand(value.brand) || value.path !== value.brand.url) return false
  if (hasSeo && !isBrandSeo(value.seo)) return false
  if (!Array.isArray(value.products) || !value.products.every(isPublicCatalogProduct)) return false
  if (hasFilters && !isBrandFilters(value.filters)) return false
  if (!isRecord(value.pagination) || !hasExactKeys(value.pagination, ['page', 'perPage', 'total'])
    || typeof value.pagination.page !== 'number' || !Number.isSafeInteger(value.pagination.page) || value.pagination.page < 1 || value.pagination.page > 10000
    || typeof value.pagination.perPage !== 'number' || !Number.isSafeInteger(value.pagination.perPage) || value.pagination.perPage < 1 || value.pagination.perPage > 48
    || typeof value.pagination.total !== 'number' || !Number.isSafeInteger(value.pagination.total) || value.pagination.total < 0) return false
  if (!isRecord(value.selected) || typeof value.selected.sort !== 'string' || !['default', 'abc', 'abc-desc', 'price', 'price-desc', 'new', 'new-desc'].includes(value.selected.sort)) return false
  const hasPriceKeys = Object.prototype.hasOwnProperty.call(value.selected, 'priceKeys')
  if (!(hasPriceKeys
    ? hasExactKeys(value.selected, ['sort', 'priceKeys']) && Array.isArray(value.selected.priceKeys) && value.selected.priceKeys.every((key) => ['1', '2', '3'].includes(key as string))
    : hasExactKeys(value.selected, ['sort']))) return false
  if (!isRecord(value.parity) || !hasExactKeys(value.parity, ['prices', 'seoOverrides'])) return false
  if (!isPriceParity(value.parity.prices)) return false
  const seoOverrides = value.parity.seoOverrides
  return isRecord(seoOverrides) && (hasExactKeys(seoOverrides, ['included']) && seoOverrides.included === true
    || hasExactKeys(seoOverrides, ['included', 'reason']) && seoOverrides.included === false && seoOverrides.reason === 'legacy_seo_override_table_not_delegated')
}

export function isBrandSlug(value: unknown): value is string {
  return isSlug(value)
}
