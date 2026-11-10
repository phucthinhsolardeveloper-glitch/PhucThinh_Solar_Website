// Same order and limits as Laravel's LeadSource: Google click ids first, so they survive the 255-character cap.
const campaignKeys = ['gclid', 'gbraid', 'wbraid', 'utm_source', 'utm_medium', 'utm_campaign', 'fbclid'] as const
const clickIds = new Set(['gclid', 'gbraid', 'wbraid', 'fbclid'])
const clickIdCode = /^[A-Za-z0-9._-]{1,200}$/
const publicPath = /^\/(?:[A-Za-z0-9._-]+(?:\/[A-Za-z0-9._-]+)*\/?)?$/
const reservedPath = /^\/(?:admin|api|_api|upload|thumbs|livewire|health|xem-truoc)(?:\/|$)/i
const campaignCode = /^[A-Za-z0-9._-]{1,60}$/
export const LEAD_CAMPAIGN_KEY = 'pts-lead-campaign'
/** An ad click is remembered this long on the device, so a lead sent on a later visit still carries it. */
export const LEAD_CAMPAIGN_DAYS = 30

function safeCampaign(params: URLSearchParams): string {
  const query = new URLSearchParams()
  for (const key of campaignKeys) {
    const code = params.get(key)
    if (code && (clickIds.has(key) ? clickIdCode : campaignCode).test(code)) query.set(key, code)
  }
  return query.toString()
}

export function leadCampaignFromUrl(value: string): string {
  try {
    const url = new URL(value)
    return ['http:', 'https:'].includes(url.protocol) ? safeCampaign(url.searchParams) : ''
  } catch {
    return ''
  }
}

export function leadSourceFromUrl(value: string, earlierCampaign = ''): string {
  try {
    const rawPath = value.match(/^https?:\/\/[^/?#]+([^?#]*)/i)?.[1] ?? ''
    if (rawPath.includes('%')) return ''
    const url = new URL(value)
    if (!['http:', 'https:'].includes(url.protocol)) return ''
    const path = url.pathname
    if (!publicPath.test(path) || /(?:^|\/)\.{1,2}(?:\/|$)/.test(path) || reservedPath.test(path) || path.length > 255) return ''
    const campaign = new URLSearchParams(safeCampaign(url.searchParams) || safeCampaign(new URLSearchParams(earlierCampaign)))
    let source = path
    for (const [key, code] of campaign) {
      const next = `${source}${source.includes('?') ? '&' : '?'}${key}=${code}`
      if (next.length <= 255) source = next
    }
    return source
  } catch {
    return ''
  }
}
