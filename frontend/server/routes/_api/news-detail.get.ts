import { isNewsDetailSlug, isPublicNewsDetailPayload } from '../../../shared/contracts/public-news-detail'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/backend-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'slug') || typeof query.slug !== 'string' || !isNewsDetailSlug(query.slug)) throw createError({ statusCode: 400, statusMessage: 'Invalid news slug' })
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/v1/news-detail',
    query: { slug: query.slug },
    validate: isPublicNewsDetailPayload,
    invalidMessage: 'Invalid news detail data contract',
    unavailableMessage: 'news detail data source unavailable',
    passThroughStatuses: [400, 404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
