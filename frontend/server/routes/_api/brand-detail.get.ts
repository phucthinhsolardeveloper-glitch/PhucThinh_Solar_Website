import { isBrandSlug, isPublicBrandDetailPayload } from '../../../shared/contracts/public-brand-detail'
import { fetchBackendPayload, requireGet } from '../../utils/backend-api'

function oneQueryValue(value: unknown): string | undefined {
  return typeof value === 'string' ? value : undefined
}
function positiveQuery(value: unknown, fallback: number, maximum: number): number | null {
  if (value === undefined) return fallback
  if (typeof value !== 'string' || !/^[1-9][0-9]*$/.test(value)) return null
  const parsed = Number(value)
  return Number.isSafeInteger(parsed) && parsed <= maximum ? parsed : null
}
const sortPattern = /^[a-z-]+$/

/** Fixed GET-only adapter for the brand detail API (/thuong-hieu/{slug}/). */
export default defineEventHandler(async (event) => {
  requireGet(event)
  const query = getQuery(event)
  const allowed = new Set(['slug', 'page', 'per_page', 'sort', 'price'])
  if (Object.keys(query).some((key) => !allowed.has(key))) throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })

  const slug = oneQueryValue(query.slug)
  if (typeof slug !== 'string' || !isBrandSlug(slug)) throw createError({ statusCode: 400, statusMessage: 'Invalid brand slug' })
  const pageInput = oneQueryValue(query.page)
  const perPageInput = oneQueryValue(query.per_page)
  const page = positiveQuery(pageInput, 1, 10000)
  const perPage = positiveQuery(perPageInput, 20, 48)
  if (page === null || perPage === null) throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })
  const sort = oneQueryValue(query.sort)
  if (sort !== undefined && !sortPattern.test(sort)) throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })
  const price = oneQueryValue(query.price)
  if (query.price !== undefined && (price === undefined || !/^[123](?:,[123])*$/.test(price) || new Set(price.split(',')).size !== price.split(',').length)) {
    throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })
  }

  const payload = await fetchBackendPayload(event, {
    endpoint: '/api/v1/brand-detail',
    query: {
      slug,
      page: pageInput === undefined ? undefined : String(page),
      per_page: perPageInput === undefined ? undefined : String(perPage),
      sort,
      price,
    },
    validate: isPublicBrandDetailPayload,
    invalidMessage: 'Invalid brand detail data contract',
    unavailableMessage: 'brand detail data source unavailable',
    passThroughStatuses: [404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
