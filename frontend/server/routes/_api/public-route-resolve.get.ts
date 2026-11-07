import { isPublicRouteResolveResult, isPublicRouteResolveSlug } from '../../../shared/contracts/public-route-resolve'
import { fetchBackendPayload, requireGet } from '../../utils/backend-api'

/** Read-only classifier that delegates slug precedence and published-state checks to Laravel. */
export default defineEventHandler(async (event) => {
  requireGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'slug') || !isPublicRouteResolveSlug(query.slug)) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid public route slug' })
  }

  const payload = await fetchBackendPayload(event, {
    endpoint: '/api/v1/public-route-resolve',
    query: { slug: query.slug },
    validate: isPublicRouteResolveResult,
    invalidMessage: 'Invalid public route resolution contract',
    unavailableMessage: 'public route classifier unavailable',
    passThroughStatuses: [404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  setHeader(event, 'X-Robots-Tag', 'noindex, nofollow, noarchive')
  return payload
})
