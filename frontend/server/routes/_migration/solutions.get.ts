import { isPublicSolutionCategoryPayload, isPublicSolutionsPayload, type PublicSolutionCategoryPayload, type PublicSolutionsPayload } from '../../../shared/contracts/public-solutions'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

/** Fixed server-only bridge; no request cookies or user-supplied upstream URL are forwarded. */
export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  const allowed = new Set(['category_type', 'category_path', 'page'])
  if (Object.keys(query).some((key) => !allowed.has(key))) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  const params = new URLSearchParams()
  for (const key of allowed) {
    const value = query[key]
    if (value !== undefined) {
      if (Array.isArray(value) || typeof value !== 'string' || !value.trim()) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
      params.set(key, value)
    }
  }
  const isCategory = params.has('category_type') || params.has('category_path')
  const validate: (payload: unknown) => payload is PublicSolutionCategoryPayload | PublicSolutionsPayload = (payload): payload is PublicSolutionCategoryPayload | PublicSolutionsPayload => isCategory
    ? isPublicSolutionCategoryPayload(payload)
    : isPublicSolutionsPayload(payload)
  const payload = await fetchMigrationPayload<PublicSolutionCategoryPayload | PublicSolutionsPayload>(event, {
    endpoint: '/api/pts_v1_solutions.php',
    query: Object.fromEntries(params.entries()),
    validate,
    invalidMessage: 'Invalid PHP solutions data contract',
    unavailableMessage: 'PHP solutions data source unavailable',
    passThroughStatuses: [400, 404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
