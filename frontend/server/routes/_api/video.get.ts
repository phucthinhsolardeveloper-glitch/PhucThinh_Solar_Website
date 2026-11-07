import { isPublicVideoPayload } from '../../../shared/contracts/public-video'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/backend-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'p')) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid video query' })
  }
  const page = query.p
  if (page !== undefined && (typeof page !== 'string' || !/^[1-9][0-9]*$/.test(page))) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid video page' })
  }
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/v1/video',
    query: { p: typeof page === 'string' ? page : undefined },
    validate: isPublicVideoPayload,
    invalidMessage: 'Invalid video data contract',
    unavailableMessage: 'video data source unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
