import { isPricing, type PublicCatalogPricing } from './public-catalog.ts'
import { isRecord, isText } from './core.ts'

/** GET /api/v1/compare?ids=1,2,3: up to four products with their spec tables lined up by label. */
export interface PublicCompareProduct {
  id: number; name: string; slug: string; url: string; code: string; brand: string; image: string; pricing: PublicCatalogPricing
}
export interface PublicCompareRow { label: string; values: string[]; same: boolean }
export interface PublicComparePayload {
  schemaVersion: 1; locale: 'vi'; max: number; products: PublicCompareProduct[]; rows: PublicCompareRow[]
}

const slug = /^[a-z0-9]+(?:-[a-z0-9]+)*$/
const plain = (value: unknown, allowEmpty = false) => typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[\u0000-\u001F\u007F]/.test(value)

function isProduct(value: unknown): value is PublicCompareProduct {
  return isRecord(value) && Number.isSafeInteger(value.id) && (value.id as number) > 0 && isText(value.name) && typeof value.slug === 'string' && slug.test(value.slug)
    && value.url === `/san-pham/${value.slug}` && plain(value.code, true) && plain(value.brand, true)
    && (value.image === '' || (typeof value.image === 'string' && value.image.startsWith('/upload/product/') && !value.image.includes('..')))
    && isPricing(value.pricing)
}

export function isPublicComparePayload(value: unknown): value is PublicComparePayload {
  if (!isRecord(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || !Number.isSafeInteger(value.max)) return false
  if (!Array.isArray(value.products) || value.products.length > (value.max as number) || !value.products.every(isProduct)) return false
  const count = value.products.length
  return Array.isArray(value.rows) && value.rows.every((row) => isRecord(row) && plain(row.label) && typeof row.same === 'boolean'
    && Array.isArray(row.values) && row.values.length === count && row.values.every((cell) => plain(cell, true)))
}
