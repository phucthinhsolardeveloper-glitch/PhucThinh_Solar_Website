// Which screen /thanh-toan/ shows. Kept pure so every branch can be tested without a browser.
export type CheckoutViewState = 'error' | 'empty' | 'quote' | 'blocked' | 'ready'

export interface CheckoutStateInput {
  loadError: boolean
  cart: { count: number, canCheckout: boolean } | null
  checkout: { cartReady: boolean } | null
}

export function checkoutViewState({ loadError, cart, checkout }: CheckoutStateInput): CheckoutViewState {
  if (loadError || !cart || !checkout) return 'error'
  if (cart.count === 0) return 'empty'
  if (!cart.canCheckout) return 'quote'
  // Cart is fine for the browser but the server session says it cannot order (e.g. stale session).
  if (!checkout.cartReady) return 'blocked'
  return 'ready'
}
