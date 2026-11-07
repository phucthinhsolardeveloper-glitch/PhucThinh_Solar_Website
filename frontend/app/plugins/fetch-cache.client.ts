// Page data is read-only and cached 60 s on the server, yet each navigation asked for it again.
// Nuxt's useFetch does not go through globalThis.$fetch, so this sits at the browser fetch level,
// which every path shares. Each caller gets its own Response copy, so nothing shares parsed data.
const CACHEABLE = /^\/_api\/(site|home|about|dealer|contact|solutions|projects|gallery|video|policy-detail|product-detail|product-extras|solution-detail|project-detail|gallery-detail|catalog|news)(\/|$|\?)/
const TTL_MS = 120_000
const WARM: Record<string, string[]> = {
  '/': ['/_api/site', '/_api/home'],
  '/san-pham/': ['/_api/site', '/_api/catalog'],
  '/lien-he/': ['/_api/site', '/_api/contact'],
}

export default defineNuxtPlugin((nuxtApp) => {
  const nativeFetch = window.fetch.bind(window)
  const entries = new Map<string, { at: number, response: Promise<Response> }>()

  window.fetch = (input: RequestInfo | URL, init?: RequestInit) => {
    const raw = typeof input === 'string' ? input : input instanceof URL ? input.href : input.url
    const method = (init?.method ?? (input instanceof Request ? input.method : 'GET')).toUpperCase()
    const url = new URL(raw, window.location.origin)
    const key = url.pathname + url.search
    if (method !== 'GET' || url.origin !== window.location.origin || !CACHEABLE.test(key)) return nativeFetch(input, init)

    const entry = entries.get(key)
    if (entry && Date.now() - entry.at < TTL_MS) return entry.response.then((response) => response.clone())
    // No abort signal here: one page leaving must not cancel a response others will reuse.
    const response = nativeFetch(key, { credentials: init?.credentials, headers: init?.headers }).then((result) => {
      if (!result.ok) entries.delete(key)
      return result
    }, (reason) => {
      entries.delete(key)
      throw reason
    })
    entries.set(key, { at: Date.now(), response })
    return response.then((result) => result.clone())
  }

  function warm(path: string) {
    const urls = WARM[path]
    if (!urls) return
    void preloadRouteComponents(path).catch(() => {})
    for (const url of urls) void window.fetch(url).catch(() => {})
  }

  nuxtApp.provide('warmRoute', warm)
  nuxtApp.hook('app:mounted', () => {
    const connection = (navigator as Navigator & { connection?: { saveData?: boolean, effectiveType?: string } }).connection
    if (connection?.saveData || /2g/.test(connection?.effectiveType ?? '')) return
    const idle = window.requestIdleCallback ?? ((run: () => void) => window.setTimeout(run, 1500))
    idle(() => Object.keys(WARM).forEach(warm))
  })
})
