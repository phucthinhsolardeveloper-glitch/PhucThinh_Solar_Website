import { LEAD_CAMPAIGN_KEY, leadCampaignFromUrl } from '#shared/lead-source'

// Keeps the latest campaign / ad click (gclid, gbraid, wbraid, fbclid, utm_*) for 30 days on this device, so a
// consultation sent days later still says which ad brought the visitor. Read back by useLeadForm.
export default defineNuxtPlugin((nuxtApp) => {
  const capture = () => {
    const campaign = leadCampaignFromUrl(window.location.href)
    if (!campaign) return
    try { localStorage.setItem(LEAD_CAMPAIGN_KEY, JSON.stringify({ campaign, at: Date.now() })) } catch { /* Storage can be unavailable. */ }
  }

  capture()
  nuxtApp.hook('page:finish', capture)
})
