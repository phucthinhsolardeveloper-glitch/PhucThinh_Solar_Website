import { LEAD_CAMPAIGN_DAYS, LEAD_CAMPAIGN_KEY } from '#shared/lead-source'

/** The remembered campaign, or '' when none or older than LEAD_CAMPAIGN_DAYS. */
export function rememberedLeadCampaign(): string {
  try {
    const saved = JSON.parse(localStorage.getItem(LEAD_CAMPAIGN_KEY) || 'null') as { campaign?: unknown; at?: unknown } | null
    if (!saved || typeof saved.campaign !== 'string' || typeof saved.at !== 'number') return ''
    return Date.now() - saved.at <= LEAD_CAMPAIGN_DAYS * 86_400_000 ? saved.campaign : ''
  } catch {
    return ''
  }
}
