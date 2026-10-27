export interface PublicCheckoutOption { id: number; name: string }
export interface PublicCheckoutPayment extends PublicCheckoutOption { description: string; code: 'legacy' | 'vnpay' }
export interface PublicCheckoutPayload {
  schemaVersion: 1
  locale: 'vi'
  csrfToken: string
  checkoutToken: string
  shippingEnabled: boolean
  cartReady: boolean
  cities: PublicCheckoutOption[]
  payments: PublicCheckoutPayment[]
}
export interface PublicCheckoutWardsPayload {
  schemaVersion: 1
  locale: 'vi'
  cityId: number
  wards: PublicCheckoutOption[]
}
export interface PublicOrderThanksPayload {
  schemaVersion: 1
  locale: 'vi'
  order: { code: string; customerName: string; totalPrice: number }
  conversionPending: boolean
}

function record(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function text(value: unknown, allowEmpty = false): value is string { return typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[\u0000-\u001F\u007F]/.test(value) }
function id(value: unknown): value is number { return typeof value === 'number' && Number.isSafeInteger(value) && value > 0 }
function option(value: unknown): value is PublicCheckoutOption { return record(value) && id(value.id) && text(value.name) }
function payment(value: unknown): value is PublicCheckoutPayment {
  return record(value) && (id(value.id) || value.id === -1) && text(value.name) && text(value.description, true)
    && (value.code === 'legacy' || value.code === 'vnpay') && (value.id === -1 ? value.code === 'vnpay' : value.code === 'legacy')
}
export function isPublicCheckoutPayload(value: unknown): value is PublicCheckoutPayload {
  return record(value) && value.schemaVersion === 1 && value.locale === 'vi'
    && typeof value.csrfToken === 'string' && /^[a-f0-9]{64}$/.test(value.csrfToken)
    && typeof value.checkoutToken === 'string' && /^[a-f0-9]{64}$/.test(value.checkoutToken)
    && typeof value.shippingEnabled === 'boolean' && typeof value.cartReady === 'boolean'
    && Array.isArray(value.cities) && value.cities.every(option)
    && Array.isArray(value.payments) && value.payments.every(payment)
}
export function isPublicCheckoutWardsPayload(value: unknown): value is PublicCheckoutWardsPayload {
  return record(value) && value.schemaVersion === 1 && value.locale === 'vi' && id(value.cityId)
    && Array.isArray(value.wards) && value.wards.every(option)
}
export function isPublicOrderThanksPayload(value: unknown): value is PublicOrderThanksPayload {
  if (!record(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || !record(value.order) || typeof value.conversionPending !== 'boolean') return false
  return text(value.order.code) && text(value.order.customerName) && typeof value.order.totalPrice === 'number'
    && Number.isSafeInteger(value.order.totalPrice) && value.order.totalPrice >= 0
}
