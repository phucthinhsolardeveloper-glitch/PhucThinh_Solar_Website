import { isPublicGalleryPayload } from '../../../shared/contracts/public-gallery'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/backend-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'p')) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid gallery query' })
  }
  const page = query.p
  if (page !== undefined && (typeof page !== 'string' || !/^[1-9][0-9]*$/.test(page))) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid gallery page' })
  }
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/v1/gallery',
    query: { p: typeof page === 'string' ? page : undefined },
    validate: isPublicGalleryPayload,
    invalidMessage: 'Invalid gallery data contract',
    unavailableMessage: 'gallery data source unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
