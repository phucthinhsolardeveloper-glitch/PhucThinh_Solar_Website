export const MIN_COMMERCE_QUANTITY = 1
export const MAX_COMMERCE_QUANTITY = 999

export function normalizeCommerceQuantity(value: unknown): number {
  const parsed = typeof value === 'number' ? value : Number(value)
  if (!Number.isFinite(parsed)) return MIN_COMMERCE_QUANTITY
  return Math.min(MAX_COMMERCE_QUANTITY, Math.max(MIN_COMMERCE_QUANTITY, Math.trunc(parsed)))
}
