import { isProductSlug, isPublicProductDetailPayload } from '../../../shared/contracts/public-product-detail'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

/** Fixed GET-only adapter for the PHP read-only PDP API. */
export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const slug = getQuery(event).slug
  if (typeof slug !== 'string' || !isProductSlug(slug)) throw createError({ statusCode: 400, statusMessage: 'Invalid product slug' })
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_product_detail.php',
    query: { slug },
    validate: isPublicProductDetailPayload,
    invalidMessage: 'Invalid PHP product detail data contract',
    unavailableMessage: 'PHP product detail data source unavailable',
    passThroughStatuses: [404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
