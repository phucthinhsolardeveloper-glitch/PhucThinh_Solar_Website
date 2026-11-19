export type Gtag = (...args: unknown[]) => void

/**
 * Send each analytics event to GA4 through exactly one gtag command, whether gtag.js or the Google tag inside GTM
 * consumes the queue. The `{ event }` object is for GTM Custom Event triggers only (Ads / Meta pixels added later):
 * it reaches GA4 only if someone adds a GA4 Event tag for it in the container, which must not be done.
 */
export function dispatchGa4Event(
  dataLayer: unknown[],
  gtag: Gtag | undefined,
  event: string,
  params: Record<string, unknown>,
) {
  const { ecommerce, ...eventParams } = params
  const ecommerceParams = ecommerce && typeof ecommerce === 'object' && !Array.isArray(ecommerce)
    ? ecommerce as Record<string, unknown>
    : {}

  // GTM keeps the last ecommerce object; clearing it stops one event's items leaking into the next trigger.
  if (ecommerce !== undefined) dataLayer.push({ ecommerce: null })
  dataLayer.push({ event, ...params })

  const command: [string, string, Record<string, unknown>] = ['event', event, { ...eventParams, ...ecommerceParams }]
  if (gtag) {
    gtag(...command)
    return
  }

  // Match Google's queue format when a page event precedes the bootstrap function.
  const queuedCommand = (function (..._args: unknown[]) { return arguments }).apply(undefined, command)
  dataLayer.push(queuedCommand)
}
