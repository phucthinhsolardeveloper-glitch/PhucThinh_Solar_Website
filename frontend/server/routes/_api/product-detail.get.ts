import { isProductSlug, isPublicProductDetailPayload } from '../../../shared/contracts/public-product-detail'
import { fetchBackendPayload, requireGet } from '../../utils/backend-api'

/** Fixed GET-only adapter for the PDP API. */
export default defineEventHandler(async (event) => {
  requireGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'slug')) throw createError({ statusCode: 400, statusMessage: 'Invalid product detail query' })
  const slug = query.slug
  if (typeof slug !== 'string' || !isProductSlug(slug)) throw createError({ statusCode: 400, statusMessage: 'Invalid product slug' })
  const payload = await fetchBackendPayload(event, {
    endpoint: '/api/v1/product-detail',
    query: { slug },
    validate: isPublicProductDetailPayload,
    invalidMessage: 'Invalid product detail data contract',
    unavailableMessage: 'product detail data source unavailable',
    passThroughStatuses: [404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
