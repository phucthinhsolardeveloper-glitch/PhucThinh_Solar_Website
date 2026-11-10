// The Google Merchant Center feed is built by Laravel from the catalogue (same rules as the CSV in Admin).
export default defineEventHandler((event) => {
  const origin = new URL(useRuntimeConfig(event).apiOrigin)
  return sendProxy(event, new URL('/google-shopping.xml', origin).toString(), { headers: {}, fetchOptions: { method: 'GET', redirect: 'manual' } })
})
