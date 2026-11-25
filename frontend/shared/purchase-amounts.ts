/**
 * GA4 purchase: `value` is the goods only, delivery is reported on its own. An API answer without the
 * split (an older backend during a release) keeps the order total.
 */
export function purchaseAmounts(order: { totalPrice: number; subtotal?: number; shippingFee?: number }): { value: number; shipping?: number } {
  const amount = (value: unknown): value is number => typeof value === 'number' && Number.isSafeInteger(value) && value >= 0
  return amount(order.subtotal) && amount(order.shippingFee)
    ? { value: order.subtotal, shipping: order.shippingFee }
    : { value: order.totalPrice }
}
