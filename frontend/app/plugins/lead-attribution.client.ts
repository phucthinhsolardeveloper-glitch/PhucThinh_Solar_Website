import { LEAD_CAMPAIGN_KEY, leadCampaignFromUrl } from '#shared/lead-source'

export default defineNuxtPlugin((nuxtApp) => {
  const capture = () => {
    const campaign = leadCampaignFromUrl(window.location.href)
    if (!campaign) return
    try { sessionStorage.setItem(LEAD_CAMPAIGN_KEY, campaign) } catch { /* Storage can be unavailable. */ }
  }

  capture()
  nuxtApp.hook('page:finish', capture)
})
