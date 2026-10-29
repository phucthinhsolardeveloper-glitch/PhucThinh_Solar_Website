import { isPublicRouteResolveResult, isPublicRouteResolveSlug } from '../../../shared/contracts/public-route-resolve'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

/** Read-only classifier that delegates slug precedence and published-state checks to PHP. */
export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'slug') || !isPublicRouteResolveSlug(query.slug)) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid public route slug' })
  }

  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_public_route_resolve.php',
    query: { slug: query.slug },
    validate: isPublicRouteResolveResult,
    invalidMessage: 'Invalid PHP public route resolution contract',
    unavailableMessage: 'PHP public route classifier unavailable',
    passThroughStatuses: [404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  setHeader(event, 'X-Robots-Tag', 'noindex, nofollow, noarchive')
  return payload
})
