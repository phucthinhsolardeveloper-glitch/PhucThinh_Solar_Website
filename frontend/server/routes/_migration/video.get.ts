import { isPublicVideoPayload } from '../../../shared/contracts/public-video'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

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
  return fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_video.php',
    query: { p: typeof page === 'string' ? page : undefined },
    validate: isPublicVideoPayload,
    invalidMessage: 'Invalid PHP video data contract',
    unavailableMessage: 'PHP video data source unavailable',
  })
})
