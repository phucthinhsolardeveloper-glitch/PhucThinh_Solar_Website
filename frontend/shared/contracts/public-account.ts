export type PublicAccountAction = 'login' | 'register' | 'forgot' | 'activate' | 'profile' | 'orders' | 'order-detail' | 'returns' | 'cancellations'

export interface PublicAccountOrderItem { name: string; image: string; code: string; quantity: number; lineTotal: number; color: string; size: string }
export interface PublicAccountOrder { id: number; code: string; date: string; name: string; phone: string; total: number; paid: boolean; status: string; returnRequested: boolean; items?: PublicAccountOrderItem[] }
export interface PublicAccountPayload {
  schemaVersion: 1
  action: PublicAccountAction
  authenticated: boolean
  csrfToken: string
  messages: string[]
  fields: Partial<Record<'fullname' | 'username' | 'gender' | 'birthday' | 'email' | 'phone' | 'address', string>>
  member: null | { fullname: string; username: string; gender: string; birthday: string; email: string; phone: string; address: string }
  orders: PublicAccountOrder[]
  order: PublicAccountOrder | null
  returns: Array<{ id: number; code: string; date: string; total: number; status: string }>
  activation?: { available: boolean }
}

const ACTIONS: readonly PublicAccountAction[] = ['login', 'register', 'forgot', 'activate', 'profile', 'orders', 'order-detail', 'returns', 'cancellations']
const FIELDS = ['fullname', 'username', 'gender', 'birthday', 'email', 'phone', 'address'] as const
function record(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }
function text(value: unknown): value is string { return typeof value === 'string' && value.length <= 2048 && !/[\u0000-\u0008\u000B\u000C\u000E-\u001F\u007F]/.test(value) }
function amount(value: unknown): value is number { return typeof value === 'number' && Number.isSafeInteger(value) && value >= 0 }
function orderItem(value: unknown): value is PublicAccountOrderItem {
  return record(value) && text(value.name) && text(value.image) && (value.image === '' || /^\/upload\/product\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(value.image)) && text(value.code) && typeof value.quantity === 'number' && Number.isSafeInteger(value.quantity) && value.quantity >= 0 && amount(value.lineTotal) && text(value.color) && text(value.size)
}
function order(value: unknown): value is PublicAccountOrder {
  return record(value) && typeof value.id === 'number' && Number.isSafeInteger(value.id) && value.id > 0 && text(value.code) && text(value.date) && text(value.name) && text(value.phone) && amount(value.total) && typeof value.paid === 'boolean' && text(value.status) && typeof value.returnRequested === 'boolean' && (value.items === undefined || Array.isArray(value.items) && value.items.every(orderItem))
}

export function isPublicAccountPayload(value: unknown): value is PublicAccountPayload {
  if (!record(value) || value.schemaVersion !== 1 || typeof value.action !== 'string' || !ACTIONS.includes(value.action as PublicAccountAction) || typeof value.authenticated !== 'boolean' || typeof value.csrfToken !== 'string' || !/^[a-f0-9]{64}$/.test(value.csrfToken)) return false
  if (!Array.isArray(value.messages) || !value.messages.every(text) || !record(value.fields) || Object.keys(value.fields).some((key) => !FIELDS.includes(key as typeof FIELDS[number])) || Object.values(value.fields).some((field) => !text(field))) return false
  const member = value.member
  if (member !== null && (!record(member) || !FIELDS.every((key) => text(member[key])))) return false
  if (!Array.isArray(value.orders) || !value.orders.every(order) || !(value.order === null || order(value.order))) return false
  if (!Array.isArray(value.returns) || !value.returns.every((row) => record(row) && typeof row.id === 'number' && Number.isSafeInteger(row.id) && row.id > 0 && text(row.code) && text(row.date) && amount(row.total) && text(row.status))) return false
  if (value.activation !== undefined && (!record(value.activation) || typeof value.activation.available !== 'boolean')) return false
  return true
}
