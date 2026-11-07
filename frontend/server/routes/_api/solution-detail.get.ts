import { isPublicSolutionDetailPayload, isSolutionSlug } from '../../../shared/contracts/public-solution-detail'
import { fetchBackendPayload, requireGet } from '../../utils/backend-api'

export default defineEventHandler(async (event) => {
  requireGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'slug') || typeof query.slug !== 'string' || !isSolutionSlug(query.slug)) throw createError({ statusCode: 400, statusMessage: 'Invalid solution slug' })
  const payload = await fetchBackendPayload(event, {
    endpoint: '/api/v1/solution-detail',
    query: { slug: query.slug },
    validate: isPublicSolutionDetailPayload,
    invalidMessage: 'Invalid solution detail data contract',
    unavailableMessage: 'solution detail data source unavailable',
    passThroughStatuses: [404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
