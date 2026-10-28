import { isPublicProjectsPayload } from '../../../shared/contracts/public-projects'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  const allowed = new Set(['category', 'page', 'per_page'])
  if (Object.keys(query).some((key) => !allowed.has(key))) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  const params = new URLSearchParams()
  for (const key of allowed) {
    const value = query[key]
    if (value !== undefined) {
      if (Array.isArray(value) || typeof value !== 'string' || !value.trim()) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
      params.set(key, value)
    }
  }
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_projects.php',
    query: Object.fromEntries(params.entries()),
    validate: isPublicProjectsPayload,
    invalidMessage: 'Invalid PHP projects data contract',
    unavailableMessage: 'PHP projects data source unavailable',
    passThroughStatuses: [400, 404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
