// Local only: forwards the browser's /api/v1/* calls (checkout, forms, page counter) to Laravel, the
// job Caddy does on the server. Off unless NUXT_LOCAL_API_PROXY=1, and only to the configured origin.
export default defineEventHandler(async (event) => {
  if (process.env.NUXT_LOCAL_API_PROXY !== '1') throw createError({ statusCode: 404, statusMessage: 'Not Found' })
  const origin = String(useRuntimeConfig(event).apiOrigin || '').replace(/\/$/, '')
  const path = String(getRouterParam(event, 'path') || '')
  if (!origin || !/^[A-Za-z0-9/_.-]{1,200}$/.test(path) || path.includes('..')) throw createError({ statusCode: 404, statusMessage: 'Not Found' })
  const here = getRequestURL(event)
  return proxyRequest(event, `${origin}/api/v1/${path}${here.search}`, {
    fetchOptions: { redirect: 'manual' },
    // A redirect Laravel builds from its own address points back at this site instead.
    onResponse(_event, response) {
      const location = response.headers.get('location')
      if (location?.startsWith(origin)) setResponseHeader(event, 'location', here.origin + location.slice(origin.length))
    },
  })
})
