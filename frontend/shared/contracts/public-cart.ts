import { isPricing, type PublicCatalogPricing } from './public-catalog.ts'

export interface PublicCartItem {
  code: string
  productId: number
  name: string
  slug: string
  url: string
  image: string
  quantity: number
  color: string
  size: string
  pricing: PublicCatalogPricing
  unitPrice: number | null
  lineTotal: number | null
}

export interface PublicCartPayload {
  schemaVersion: 1
  locale: 'vi'
  currency: 'VND'
  csrfToken: string
  count: number
  quantityCount: number
  items: PublicCartItem[]
  subtotal: number | null
  canCheckout: boolean
}

const SLUG = /^[a-z0-9]+(?:-[a-z0-9]+)*$/
const CODE = /^[a-f0-9]{32}$/
function record(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function text(value: unknown, allowEmpty = false): value is string { return typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[\u0000-\u001F\u007F]/.test(value) }
function positiveInt(value: unknown, allowZero = false): value is number { return typeof value === 'number' && Number.isSafeInteger(value) && (allowZero ? value >= 0 : value > 0) }
function money(value: unknown, nullable = false): value is number | null { return nullable && value === null || typeof value === 'number' && Number.isSafeInteger(value) && value >= 0 }
function image(value: unknown): value is string { return value === '' || text(value) && /^\/upload\/product\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(value) }
function cartItem(value: unknown): value is PublicCartItem {
  if (!record(value) || !CODE.test(String(value.code ?? '')) || !positiveInt(value.productId) || !text(value.name) || typeof value.slug !== 'string' || !SLUG.test(value.slug)) return false
  if (value.url !== `/san-pham/${value.slug}` || !image(value.image) || !positiveInt(value.quantity) || value.quantity > 999 || !text(value.color, true) || !text(value.size, true) || !isPricing(value.pricing)) return false
  if (!money(value.unitPrice, true) || !money(value.lineTotal, true)) return false
  if (value.pricing.mode === 'quote') return value.unitPrice === null && value.lineTotal === null
  const expectedUnit = value.pricing.salePrice && value.pricing.salePrice > 0 ? value.pricing.salePrice : value.pricing.regularPrice
  return value.unitPrice === expectedUnit && value.lineTotal === (expectedUnit === null ? null : expectedUnit * value.quantity)
}

export function isPublicCartPayload(value: unknown): value is PublicCartPayload {
  if (!record(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || value.currency !== 'VND' || typeof value.csrfToken !== 'string' || !/^[a-f0-9]{64}$/.test(value.csrfToken)) return false
  if (!positiveInt(value.count, true) || !positiveInt(value.quantityCount, true) || !Array.isArray(value.items) || value.items.length > 100 || !value.items.every(cartItem)) return false
  if (value.count !== value.items.length || value.quantityCount !== value.items.reduce((sum, item) => sum + item.quantity, 0)) return false
  const hasQuote = value.items.some((item) => item.lineTotal === null)
  const subtotal = value.items.reduce((sum, item) => sum + (item.lineTotal ?? 0), 0)
  if (hasQuote) {
    if (value.subtotal !== null || value.canCheckout !== false) return false
  } else if (value.subtotal !== subtotal || value.canCheckout !== (value.items.length > 0)) return false
  return true
}
