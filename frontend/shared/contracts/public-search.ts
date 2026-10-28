import { isPublicCatalogProduct, type PublicCatalogProduct } from './public-catalog.ts'

export interface PublicSearchPayload {
  schemaVersion: 1
  locale: 'vi'
  keyword: string
  products: PublicCatalogProduct[]
  pagination: { page: number; perPage: number; total: number }
}

function record(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function keyword(value: unknown): value is string { return typeof value === 'string' && value.trim() !== '' && value.length <= 100 && !/[\u0000-\u001F\u007F]/.test(value) }
function positiveInt(value: unknown, min: number, max: number): value is number { return typeof value === 'number' && Number.isSafeInteger(value) && value >= min && value <= max }

export function isPublicSearchPayload(value: unknown): value is PublicSearchPayload {
  if (!record(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || !keyword(value.keyword)) return false
  if (!Array.isArray(value.products) || value.products.length > 48 || !value.products.every(isPublicCatalogProduct)) return false
  if (!record(value.pagination)) return false
  return positiveInt(value.pagination.page, 1, 10000)
    && positiveInt(value.pagination.perPage, 1, 48)
    && positiveInt(value.pagination.total, 0, Number.MAX_SAFE_INTEGER)
}
