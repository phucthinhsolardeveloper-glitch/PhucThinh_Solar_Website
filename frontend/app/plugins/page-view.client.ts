// Counts pages seen for the Admin dashboard. No cookie: the first page of a visit carries the referrer
// and campaign, later pages are marked as part of the same visit. Automated browsers are not counted.
import { useRouter } from '#app/composables/router'
import { sanitizeAnalyticsUrl } from '~/utils/analyticsPageContext'

const VISIT_KEY = 'pts-visit'
const CAMPAIGN = /[?&](utm_source|utm_medium|utm_campaign|gclid|fbclid|zarsrc)=([^&#]{1,80})/i

interface AnalyticsRouteContext {
  path: string
  referrer: string
  rendered: boolean
}

declare global {
  interface Window {
    __ptsAnalyticsRouteContext?: AnalyticsRouteContext
  }
}

export default defineNuxtPlugin((nuxtApp) => {
  const router = useRouter()
  let last = ''
  window.__ptsAnalyticsRouteContext = {
    path: window.location.pathname,
    referrer: document.referrer,
    rendered: false,
  }

  const setGooglePageContext = (referrer: string) => {
    const gtag = (window as Window & { gtag?: (...args: unknown[]) => void }).gtag
    if (typeof gtag !== 'function') return
    gtag('set', {
      page_location: sanitizeAnalyticsUrl(window.location.href),
      page_referrer: sanitizeAnalyticsUrl(referrer),
    })
  }
  setGooglePageContext(document.referrer)

  router.afterEach((to, from, failure) => {
    if (failure) return
    const context = {
      path: to.path,
      referrer: from.matched.length ? `${window.location.origin}${from.fullPath}` : document.referrer,
      rendered: false,
    }
    window.__ptsAnalyticsRouteContext = context
    setGooglePageContext(context.referrer)
  })

  const markRendered = () => {
    const context = window.__ptsAnalyticsRouteContext
    if (context?.path === window.location.pathname) context.rendered = true
  }

  const send = () => {
    markRendered()
    if (navigator.webdriver) return
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
      referrer: entry ? sanitizeAnalyticsUrl(document.referrer).slice(0, 500) : '',
      campaign: entry ? (window.location.search.match(CAMPAIGN)?.[1] ?? '') : '',
    })
    try {
      void fetch('/api/v1/track', { method: 'POST', body, headers: { 'content-type': 'application/json' }, keepalive: true, referrerPolicy: 'origin' }).catch(() => {})
    } catch { /* Counting must never break the page. */ }
  }

  nuxtApp.hook('app:mounted', send)
  nuxtApp.hook('page:finish', send)
  nuxtApp.hook('page:loading:end', markRendered)
})
