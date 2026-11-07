import { isPublicProjectsPayload } from '../../../shared/contracts/public-projects'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/backend-api'

function oneQueryValue(value: unknown): string | undefined {
  return typeof value === 'string' ? value : undefined
}

function positiveQuery(value: unknown, fallback: number, maximum: number): number | null {
  if (value === undefined) return fallback
  if (typeof value !== 'string' || !/^[1-9][0-9]*$/.test(value)) return null
  const parsed = Number(value)
  return Number.isSafeInteger(parsed) && parsed <= maximum ? parsed : null
}

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  // The projects endpoint deliberately has a smaller contract than the
  // news taxonomy endpoint: category is a single published list slug.
  const allowed = new Set(['category', 'page', 'per_page'])
  if (Object.keys(query).some((key) => !allowed.has(key))) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  const category = oneQueryValue(query.category)
  if (query.category !== undefined && (category === undefined || !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(category))) {
    throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  }
  const pageInput = oneQueryValue(query.page)
  const perPageInput = oneQueryValue(query.per_page)
  const page = positiveQuery(pageInput, 1, 10000)
  const perPage = positiveQuery(perPageInput, 12, 48)
  if (page === null || perPage === null) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  const params = new URLSearchParams()
  if (category !== undefined) params.set('category', category)
  if (pageInput !== undefined) params.set('page', String(page))
  if (perPageInput !== undefined) params.set('per_page', String(perPage))
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/v1/projects',
    query: Object.fromEntries(params.entries()),
    validate: isPublicProjectsPayload,
    invalidMessage: 'Invalid projects data contract',
    unavailableMessage: 'projects data source unavailable',
    passThroughStatuses: [400, 404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
