import { isPublicCatalogProduct, type PublicCatalogProduct } from './public-catalog.ts'

export interface PublicSearchPayload {
  schemaVersion: 1
  locale: 'vi'
  keyword: string
  products: PublicCatalogProduct[]
  pagination: { page: number; perPage: number; total: number }
  filters?: {
    brands: Array<{ id: number; name: string }>
    priceRanges: Array<{ key: string; label: string }>
  }
  selected?: {
    brandIds: number[]
    priceKeys: string[]
  }
}

function record(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function keyword(value: unknown): value is string { return typeof value === 'string' && value.trim() !== '' && value.length <= 100 && !/[\u0000-\u001F\u007F]/.test(value) }
function positiveInt(value: unknown, min: number, max: number): value is number { return typeof value === 'number' && Number.isSafeInteger(value) && value >= min && value <= max }

export function isPublicSearchPayload(value: unknown): value is PublicSearchPayload {
  if (!record(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || !keyword(value.keyword)) return false
  if (!Array.isArray(value.products) || value.products.length > 48 || !value.products.every(isPublicCatalogProduct)) return false
  if (!record(value.pagination)) return false
  if (!(positiveInt(value.pagination.page, 1, 10000)
    && positiveInt(value.pagination.perPage, 1, 48)
    && positiveInt(value.pagination.total, 0, Number.MAX_SAFE_INTEGER))) return false

  const hasFilters = Object.prototype.hasOwnProperty.call(value, 'filters')
  const hasSelected = Object.prototype.hasOwnProperty.call(value, 'selected')
  if (hasFilters !== hasSelected) return false
  if (!hasFilters) return true

  const filters = value.filters
  const selected = value.selected
  return record(filters)
    && Array.isArray(filters.brands)
    && filters.brands.length <= 500
    && filters.brands.every((brand) => record(brand)
      && positiveInt(brand.id, 1, Number.MAX_SAFE_INTEGER)
      && typeof brand.name === 'string' && brand.name.trim() !== '' && !/[\u0000-\u001F\u007F]/.test(brand.name))
    && Array.isArray(filters.priceRanges)
    && filters.priceRanges.every((range) => record(range)
      && typeof range.key === 'string' && /^[123]$/.test(range.key)
      && typeof range.label === 'string' && range.label.trim() !== '' && !/[\u0000-\u001F\u007F]/.test(range.label))
    && record(selected)
    && Array.isArray(selected.brandIds)
    && selected.brandIds.every((id) => positiveInt(id, 1, Number.MAX_SAFE_INTEGER))
    && Array.isArray(selected.priceKeys)
    && selected.priceKeys.every((key) => typeof key === 'string' && /^[123]$/.test(key))
}
