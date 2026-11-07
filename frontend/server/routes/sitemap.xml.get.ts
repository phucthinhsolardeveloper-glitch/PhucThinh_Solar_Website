// The sitemap is built by Laravel from the content database.
export default defineEventHandler((event) => {
  const origin = new URL(useRuntimeConfig(event).apiOrigin)
  return sendProxy(event, new URL('/sitemap.xml', origin).toString(), { headers: {}, fetchOptions: { method: 'GET', redirect: 'manual' } })
})
