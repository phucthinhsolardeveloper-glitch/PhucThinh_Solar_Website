export interface PublicCheckoutOption { id: number; name: string }
export interface PublicCheckoutPayment extends PublicCheckoutOption { description: string; code: 'manual' | 'vnpay' }
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
export interface PublicCheckoutNoticeForm {
  fullname?: string
  phone?: string
  email?: string
  city?: string
  ward?: string
  address?: string
  requirements?: string
  payments?: string
}
export interface PublicCheckoutNoticePayload {
  schemaVersion: 1
  locale: 'vi'
  messages: string[]
  toast: { status: 'error' | 'success'; text: string } | null
  form: PublicCheckoutNoticeForm | null
}
export interface PublicOrderThanksPayload {
  schemaVersion: 1
  locale: 'vi'
  order: { code: string; customerName: string; totalPrice: number }
  paymentState: 'accepted' | 'pending' | 'paid' | 'failed' | 'awaiting_transfer'
  conversionPending: boolean
  /** Fixed VietQR instruction of a bank-transfer order; scanning it pays nothing by itself. */
  transfer?: PublicOrderTransfer
}
export interface PublicOrderTransfer {
  bankName: string
  accountNo: string
  accountName: string
  amount: number
  note: string
  qrImage: string
  reported: boolean
}

function record(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function text(value: unknown, allowEmpty = false): value is string { return typeof value === 'string' && (allowEmpty || value.trim() !== '') && !/[\u0000-\u001F\u007F]/.test(value) }
function id(value: unknown): value is number { return typeof value === 'number' && Number.isSafeInteger(value) && value > 0 }
function option(value: unknown): value is PublicCheckoutOption { return record(value) && id(value.id) && text(value.name) }
function payment(value: unknown): value is PublicCheckoutPayment {
  return record(value) && (id(value.id) || value.id === -1) && text(value.name) && text(value.description, true)
    && (value.code === 'manual' || value.code === 'vnpay') && (value.id === -1 ? value.code === 'vnpay' : value.code === 'manual')
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
export function isPublicCheckoutNoticePayload(value: unknown): value is PublicCheckoutNoticePayload {
  if (!record(value) || value.schemaVersion !== 1 || value.locale !== 'vi'
    || !Array.isArray(value.messages) || value.messages.length > 8
    || !value.messages.every((message) => text(message) && message.length <= 240)) return false
  if (value.toast !== null && (!record(value.toast) || !['error', 'success'].includes(String(value.toast.status))
    || !text(value.toast.text) || value.toast.text.length > 500)) return false
  if (value.form === null) return true
  if (!record(value.form)) return false
  const form = value.form
  const limits: Record<string, number> = {
    fullname: 150, phone: 30, email: 190, city: 12, ward: 12,
    address: 255, requirements: 1000, payments: 12,
  }
  return Object.entries(form).every(([key, item]) => Object.hasOwn(limits, key)
    && text(item) && item.length <= limits[key]!)
    && ['city', 'ward', 'payments'].every((key) => form[key] === undefined || /^-?\d{1,12}$/.test(form[key] as string))
}
function isOrderTransfer(value: unknown): value is PublicOrderTransfer {
  return record(value) && text(value.bankName) && typeof value.accountNo === 'string' && /^[0-9A-Za-z]{4,19}$/.test(value.accountNo)
    && text(value.accountName) && typeof value.amount === 'number' && Number.isSafeInteger(value.amount) && value.amount > 0
    && typeof value.note === 'string' && /^[A-Z0-9]{4,25}$/.test(value.note)
    && typeof value.qrImage === 'string' && value.qrImage.startsWith('data:image/svg+xml;base64,') && value.qrImage.length < 200000
    && typeof value.reported === 'boolean'
}
export function isPublicOrderThanksPayload(value: unknown): value is PublicOrderThanksPayload {
  if (!record(value) || value.schemaVersion !== 1 || value.locale !== 'vi' || !record(value.order)
    || typeof value.paymentState !== 'string' || !['accepted', 'pending', 'paid', 'failed', 'awaiting_transfer'].includes(value.paymentState)
    || typeof value.conversionPending !== 'boolean') return false
  if (value.conversionPending && !['accepted', 'paid', 'awaiting_transfer'].includes(value.paymentState)) return false
  if (value.paymentState === 'awaiting_transfer' && value.transfer === undefined) return false
  if (value.transfer !== undefined && !isOrderTransfer(value.transfer)) return false
  return text(value.order.code) && text(value.order.customerName) && typeof value.order.totalPrice === 'number'
    && Number.isSafeInteger(value.order.totalPrice) && value.order.totalPrice >= 0
}
