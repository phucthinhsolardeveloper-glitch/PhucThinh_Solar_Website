import { isNewsDetailSlug, isPublicNewsDetailPayload } from '../../../shared/contracts/public-news-detail'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'slug') || typeof query.slug !== 'string' || !isNewsDetailSlug(query.slug)) throw createError({ statusCode: 400, statusMessage: 'Invalid news slug' })
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_news_detail.php',
    query: { slug: query.slug },
    validate: isPublicNewsDetailPayload,
    invalidMessage: 'Invalid PHP news detail data contract',
    unavailableMessage: 'PHP news detail data source unavailable',
    passThroughStatuses: [400, 404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
