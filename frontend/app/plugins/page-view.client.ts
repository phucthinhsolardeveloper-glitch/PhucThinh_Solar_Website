// Counts pages seen for the Admin dashboard. No cookie: the first page of a visit carries the referrer
// and campaign, later pages are marked as part of the same visit. Automated browsers are not counted.
const VISIT_KEY = 'pts-visit'
const CAMPAIGN = /[?&](utm_source|utm_medium|utm_campaign|gclid|fbclid|zarsrc)=([^&#]{1,80})/i

export default defineNuxtPlugin((nuxtApp) => {
  if (navigator.webdriver) return
  let last = ''

  const send = () => {
    const path = window.location.pathname
    if (path === last) return
    last = path
    let entry = true
    try {
      entry = sessionStorage.getItem(VISIT_KEY) !== '1'
      sessionStorage.setItem(VISIT_KEY, '1')
    } catch { /* Storage can be unavailable; then every page counts as an entry. */ }
    const body = JSON.stringify({
      path,
      entry,
      referrer: entry ? document.referrer.slice(0, 500) : '',
      campaign: entry ? (window.location.search.match(CAMPAIGN)?.[1] ?? '') : '',
    })
    try {
      if (!navigator.sendBeacon?.('/api/v1/track', new Blob([body], { type: 'application/json' }))) {
        void fetch('/api/v1/track', { method: 'POST', body, headers: { 'content-type': 'application/json' }, keepalive: true }).catch(() => {})
      }
    } catch { /* Counting must never break the page. */ }
  }

  nuxtApp.hook('app:mounted', send)
  nuxtApp.hook('page:finish', send)
})
