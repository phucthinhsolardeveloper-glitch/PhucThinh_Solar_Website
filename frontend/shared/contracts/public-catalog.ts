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
}

export interface PublicCatalogPayload {
  schemaVersion: 1
  locale: 'vi'
  category: [] | PublicCatalogCategory
  categories: PublicCatalogCategory[]
  products: PublicCatalogProduct[]
  pagination: { page: number; perPage: number; total: number }
  parity: {
    prices: { included: false; reason: string }
    filters: { included: false; reason: string }
    sort: { included: false; reason: string }
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

function isProduct(value: unknown): value is PublicCatalogProduct {
  return isRecord(value) && hasExactKeys(value, ['id', 'name', 'slug', 'url', 'media']) && isSafeId(value.id) && isSafeText(value.name) && isSlug(value.slug) && value.url === `/san-pham/${value.slug}/` && Array.isArray(value.media) && value.media.every((path) => typeof path === 'string' && /^\/upload\/product\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(path))
}

function isParity(value: unknown, reason: string): value is { included: false; reason: string } {
  return isRecord(value) && hasExactKeys(value, ['included', 'reason']) && value.included === false && value.reason === reason
}

export function isPublicCatalogPayload(value: unknown): value is PublicCatalogPayload {
  if (!isRecord(value) || !hasExactKeys(value, ['schemaVersion', 'locale', 'category', 'categories', 'products', 'pagination', 'parity']) || value.schemaVersion !== 1 || value.locale !== 'vi') return false
  if (!(Array.isArray(value.category) && value.category.length === 0 || isCategory(value.category))) return false
  if (!Array.isArray(value.categories) || !value.categories.every(isCategory) || !Array.isArray(value.products) || !value.products.every(isProduct)) return false
  if (!isRecord(value.pagination) || !hasExactKeys(value.pagination, ['page', 'perPage', 'total']) || !Number.isSafeInteger(value.pagination.page) || value.pagination.page < 1 || value.pagination.page > 10000 || !Number.isSafeInteger(value.pagination.perPage) || value.pagination.perPage < 1 || value.pagination.perPage > 48 || !Number.isSafeInteger(value.pagination.total) || value.pagination.total < 0) return false
  if (!isRecord(value.parity) || !hasExactKeys(value.parity, ['prices', 'filters', 'sort'])) return false
  return isParity(value.parity.prices, 'public_pricing_authority_not_delegated') && isParity(value.parity.filters, 'legacy_filter_parity_not_verified') && isParity(value.parity.sort, 'legacy_sort_parity_not_verified')
}

export function isCatalogSlugChain(value: string): boolean {
  return value === '' || /^(?:[a-z0-9]+(?:-[a-z0-9]+)*){1}(?:\/[a-z0-9]+(?:-[a-z0-9]+)*){0,3}$/.test(value)
}
