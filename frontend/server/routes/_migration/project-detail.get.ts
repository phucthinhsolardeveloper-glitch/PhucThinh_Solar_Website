import { isProjectSlug, isPublicProjectDetailPayload } from '../../../shared/contracts/public-project-detail'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'slug')) throw createError({ statusCode: 400, statusMessage: 'Invalid project detail query' })
  const slug = query.slug
  if (typeof slug !== 'string' || !isProjectSlug(slug)) throw createError({ statusCode: 400, statusMessage: 'Invalid project slug' })
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_project_detail.php',
    query: { slug },
    validate: isPublicProjectDetailPayload,
    invalidMessage: 'Invalid PHP project detail data contract',
    unavailableMessage: 'PHP project detail data source unavailable',
    passThroughStatuses: [400, 404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
