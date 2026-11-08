const campaignKeys = ['utm_source', 'utm_medium', 'utm_campaign'] as const
const publicPath = /^\/(?:[A-Za-z0-9._-]+(?:\/[A-Za-z0-9._-]+)*\/?)?$/
const reservedPath = /^\/(?:admin|api|_api|upload|thumbs|livewire|health|xem-truoc)(?:\/|$)/i
const campaignCode = /^[A-Za-z0-9._-]{1,60}$/
export const LEAD_CAMPAIGN_KEY = 'pts-lead-campaign'

function safeCampaign(params: URLSearchParams): string {
  const query = new URLSearchParams()
  for (const key of campaignKeys) {
    const code = params.get(key)
    if (code && campaignCode.test(code)) query.set(key, code)
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
    const campaign = safeCampaign(url.searchParams) || safeCampaign(new URLSearchParams(earlierCampaign))
    const source = `${path}${campaign ? `?${campaign}` : ''}`
    return source.length <= 255 ? source : path
  } catch {
    return ''
  }
}
