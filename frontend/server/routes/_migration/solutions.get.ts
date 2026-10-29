import { isPublicSolutionsPayload } from '../../../shared/contracts/public-solutions'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

/** Fixed server-only bridge; no request cookies or user-supplied upstream URL are forwarded. */
export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'category')) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  const category = query.category
  if (category !== undefined && (Array.isArray(category) || typeof category !== 'string' || !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(category))) {
    throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  }
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_solutions.php',
    query: category === undefined ? {} : { category },
    validate: isPublicSolutionsPayload,
    invalidMessage: 'Invalid PHP solutions data contract',
    unavailableMessage: 'PHP solutions data source unavailable',
    passThroughStatuses: [400, 404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
