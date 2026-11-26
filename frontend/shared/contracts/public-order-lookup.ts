import type { PublicOrderTransfer } from './public-checkout'

/** POST /api/v1/order-lookup: one order found by its code and the phone it was placed with. No address, e-mail or phone. */
export interface PublicOrderLookup {
  code: string
  placedAt: string
  status: 'new' | 'confirmed' | 'shipping' | 'done' | 'cancelled' | string
  statusLabel: string
  paymentStatus: string
  paymentLabel: string
  paymentMethod: string
  items: { name: string, option: string, quantity: number, price: number, total: number }[]
  subtotal: number
  shippingFee: number
  total: number
  shipment: { carrier: string, billCode: string, status: string, statusLabel: string, events: { at: string, label: string }[] } | null
  transfer?: PublicOrderTransfer
}

function record(value: unknown): value is Record<string, unknown> { return value !== null && typeof value === 'object' && !Array.isArray(value) }

export function isPublicOrderLookupPayload(value: unknown): value is { schemaVersion: 1, order: PublicOrderLookup } {
  if (!record(value) || value.schemaVersion !== 1 || !record(value.order)) return false
  const order = value.order
  return typeof order.code === 'string' && typeof order.statusLabel === 'string' && Array.isArray(order.items)
    && typeof order.total === 'number' && (order.shipment === null || record(order.shipment))
}
