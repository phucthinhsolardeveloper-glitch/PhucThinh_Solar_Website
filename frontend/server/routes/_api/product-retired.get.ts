import { isProductSlug } from '../../../shared/contracts/public-product-detail'
import { isPublicProductRetiredPayload } from '../../../shared/contracts/public-product-retired'
import { fetchBackendPayload, requireGet } from '../../utils/backend-api'

/** Fixed GET-only adapter: the name and nearest category of a hidden product, for visitors of its old link. */
export default defineEventHandler(async (event) => {
  requireGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'slug')) throw createError({ statusCode: 400, statusMessage: 'Invalid product query' })
  const slug = query.slug
  if (typeof slug !== 'string' || !isProductSlug(slug)) throw createError({ statusCode: 400, statusMessage: 'Invalid product slug' })
  const payload = await fetchBackendPayload(event, {
    endpoint: '/api/v1/product-retired',
    query: { slug },
    validate: isPublicProductRetiredPayload,
    invalidMessage: 'Invalid retired product data contract',
    unavailableMessage: 'retired product data source unavailable',
    passThroughStatuses: [404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
