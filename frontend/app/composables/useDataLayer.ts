// Events for Google Tag Manager in the GA4 shapes (view_item, add_to_cart, begin_checkout, purchase, generate_lead,
// click_call, click_zalo). Only figures the shop already shows: no names, phones or e-mails of customers ever go here.
// Harmless when GTM is off: the array just collects and nobody reads it.
export interface DataLayerItem { item_id: string; item_name: string; price?: number; quantity?: number; item_brand?: string; item_category?: string }

type Layer = Array<Record<string, unknown>>

function layer(): Layer | null {
  if (!import.meta.client) return null
  const w = window as Window & { dataLayer?: Layer }
  return (w.dataLayer ??= [])
}

export function pushDataLayer(event: string, params: Record<string, unknown> = {}) {
  const target = layer()
  if (!target) return
  // GA4 keeps the last ecommerce object; clearing it stops one event's items leaking into the next.
  if ('ecommerce' in params) target.push({ ecommerce: null })
  target.push({ event, ...params })
  // Direct GA4 (Admin "Mã đo Google Analytics 4") reads gtag() calls, not these GTM objects: send the same event.
  const gtag = (window as Window & { gtag?: (...args: unknown[]) => void }).gtag
  if (typeof gtag === 'function') {
    const { ecommerce, ...rest } = params as { ecommerce?: Record<string, unknown> } & Record<string, unknown>
    gtag('event', event, { ...rest, ...(ecommerce ?? {}) })
  }
}

export function pushEcommerce(event: 'view_item' | 'add_to_cart' | 'begin_checkout' | 'purchase', items: DataLayerItem[], extra: Record<string, unknown> = {}) {
  const value = items.reduce((sum, item) => sum + (item.price ?? 0) * (item.quantity ?? 1), 0)
  pushDataLayer(event, { ecommerce: { currency: 'VND', value, items, ...extra } })
}
