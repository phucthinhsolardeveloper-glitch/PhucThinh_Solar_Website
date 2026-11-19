export interface AnalyticsPageContext extends Record<string, unknown> {
  page_location: string
  page_title: string
  page_referrer?: string
}

// Only campaign and ad-click identifiers survive: GA4 attribution reads them from page_location.
// Everything else in a query (emails, phones, order tokens, search text) is dropped, and so is the fragment.
const ATTRIBUTION_PARAM = /^(?:utm_[a-z_]{1,40}|gclid|gbraid|wbraid|fbclid)$/
// Pages whose query carries a secret (the order thank-you page has ?token=): no query at all.
const NO_QUERY_PATH = /^\/cam-on(?:\/|$)/

export function sanitizeAnalyticsUrl(value: string, base?: string): string {
  if (!value.trim()) return ''
  try {
    const url = new URL(value, base)
    if (url.protocol !== 'http:' && url.protocol !== 'https:') return ''
    const kept = new URLSearchParams()
    if (!NO_QUERY_PATH.test(url.pathname)) {
      for (const [key, param] of url.searchParams) {
        if (ATTRIBUTION_PARAM.test(key) && param) kept.append(key, param.slice(0, 200))
      }
    }
    const query = kept.toString()
    return `${url.origin}${url.pathname}${query ? `?${query}` : ''}`
  } catch {
    return ''
  }
}

export function buildAnalyticsPageContext(currentUrl: string, title: string, referrer: string): AnalyticsPageContext {
  const pageLocation = sanitizeAnalyticsUrl(currentUrl)
  const pageReferrer = sanitizeAnalyticsUrl(referrer, currentUrl)
  return {
    page_location: pageLocation,
    page_title: title.slice(0, 300),
    ...(pageReferrer ? { page_referrer: pageReferrer } : {}),
  }
}
