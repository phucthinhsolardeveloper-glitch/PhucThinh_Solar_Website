import { isPublicSolutionDetailPayload, isSolutionSlug } from '../../../shared/contracts/public-solution-detail'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'slug') || typeof query.slug !== 'string' || !isSolutionSlug(query.slug)) throw createError({ statusCode: 400, statusMessage: 'Invalid solution slug' })
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_solution_detail.php',
    query: { slug: query.slug },
    validate: isPublicSolutionDetailPayload,
    invalidMessage: 'Invalid PHP solution detail data contract',
    unavailableMessage: 'PHP solution detail data source unavailable',
    passThroughStatuses: [404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
