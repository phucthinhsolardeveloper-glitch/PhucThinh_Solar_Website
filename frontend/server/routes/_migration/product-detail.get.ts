import { isProductSlug, isPublicProductDetailPayload } from '../../../shared/contracts/public-product-detail'

/** Fixed GET-only adapter for the PHP read-only PDP API. */
export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') {
    setHeader(event, 'Allow', 'GET')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
  const slug = getQuery(event).slug
  if (typeof slug !== 'string' || !isProductSlug(slug)) throw createError({ statusCode: 400, statusMessage: 'Invalid product slug' })
  const config = useRuntimeConfig(event)
  const upstream = `${String(config.phpOrigin).replace(/\/$/, '')}/api/pts_v1_product_detail.php`
  try {
    const payload = await $fetch<unknown>(upstream, { method: 'GET', query: { slug }, timeout: 5000 })
    if (!isPublicProductDetailPayload(payload)) throw new Error('invalid contract')
    setHeader(event, 'Cache-Control', 'private, no-store')
    return payload
  } catch {
    throw createError({ statusCode: 502, statusMessage: 'PHP product detail data source unavailable' })
  }
})
