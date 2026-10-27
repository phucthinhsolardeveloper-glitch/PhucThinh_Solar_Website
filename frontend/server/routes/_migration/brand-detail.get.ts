import { isBrandSlug, isPublicBrandDetailPayload } from '../../../shared/contracts/public-brand-detail'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

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

/** Fixed GET-only adapter for the PHP read-only brand detail API (/thuong-hieu/{slug}/). */
export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  const allowed = new Set(['slug', 'page', 'per_page', 'sort'])
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

  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_brand_detail.php',
    query: {
      slug,
      page: pageInput === undefined ? undefined : String(page),
      per_page: perPageInput === undefined ? undefined : String(perPage),
      sort,
    },
    validate: isPublicBrandDetailPayload,
    invalidMessage: 'Invalid PHP brand detail data contract',
    unavailableMessage: 'PHP brand detail data source unavailable',
    passThroughStatuses: [404],
  })
  setHeader(event, 'Cache-Control', 'private, max-age=60, stale-while-revalidate=60')
  return payload
})
