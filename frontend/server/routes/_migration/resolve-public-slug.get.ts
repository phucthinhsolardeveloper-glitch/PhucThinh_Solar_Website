import { normalizePublicRouteResolution } from '../../../shared/contracts/public-route-resolution'
import { migrationPhpEndpoint, requireMigrationGet } from '../../utils/migration-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'slug') || typeof query.slug !== 'string' || !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(query.slug)) {
    throw createError({ statusCode: 400, statusMessage: 'Invalid public slug' })
  }
  let raw: unknown
  try {
    raw = await $fetch<unknown>(migrationPhpEndpoint(event, '/api/pts_v1_public_route_resolve.php'), {
      method: 'GET',
      query: { slug: query.slug },
      timeout: 5000,
    })
  } catch (error) {
    const status = error && typeof error === 'object' && typeof (error as { statusCode?: unknown }).statusCode === 'number'
      ? (error as { statusCode: number }).statusCode
      : undefined
    if (status === 400 || status === 404) throw error
    throw createError({ statusCode: 502, statusMessage: 'Public route resolver unavailable' })
  }
  const payload = normalizePublicRouteResolution(raw)
  if (!payload) throw createError({ statusCode: 502, statusMessage: 'Invalid public route resolution contract' })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
