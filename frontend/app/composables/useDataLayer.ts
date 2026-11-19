// Events for Google Tag Manager in the GA4 shapes (view_item, add_to_cart, begin_checkout, purchase, generate_lead,
// click_call, click_zalo). Only figures the shop already shows: no names, phones or e-mails of customers ever go here.
// Harmless when GTM is off: the array just collects and nobody reads it.
import { dispatchGa4Event } from '~/utils/ga4Event'

export interface DataLayerItem { item_id: string; item_name: string; price?: number; quantity?: number; item_brand?: string; item_category?: string }

type Layer = unknown[]

function layer(): Layer | null {
  if (!import.meta.client) return null
  const w = window as Window & { dataLayer?: Layer }
  return (w.dataLayer ??= [])
}

export function pushDataLayer(event: string, params: Record<string, unknown> = {}) {
  const target = layer()
  if (!target) return
  const gtag = (window as Window & { gtag?: (...args: unknown[]) => void }).gtag
  // One gtag command reaches GA4 (direct or through the Google tag in GTM); the `{ event }` object is for GTM triggers.
  dispatchGa4Event(target, typeof gtag === 'function' ? gtag.bind(window) : undefined, event, params)
}

export function pushEcommerce(event: 'view_item' | 'add_to_cart' | 'begin_checkout' | 'purchase', items: DataLayerItem[], extra: Record<string, unknown> = {}) {
  const value = items.reduce((sum, item) => sum + (item.price ?? 0) * (item.quantity ?? 1), 0)
  pushDataLayer(event, { ecommerce: { currency: 'VND', value, items, ...extra } })
}
