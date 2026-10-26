import { isCatalogSlugChain, isPublicCatalogPayload } from '../../../shared/contracts/public-catalog'

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
  const query = getQuery(event)
  const allowed = new Set(['category', 'page', 'per_page'])
  if (Object.keys(query).some((key) => !allowed.has(key))) throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })

  const category = oneQueryValue(query.category)
  const pageInput = oneQueryValue(query.page)
  const perPageInput = oneQueryValue(query.per_page)
  if ((query.category !== undefined && category === undefined) || (query.page !== undefined && pageInput === undefined) || (query.per_page !== undefined && perPageInput === undefined)) throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })
  if (category !== undefined && !isCatalogSlugChain(category)) throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })
  const page = positiveQuery(pageInput, 1, 10000)
  const perPage = positiveQuery(perPageInput, 20, 48)
  if (page === null || perPage === null) throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })

  const config = useRuntimeConfig(event)
  const params = new URLSearchParams()
  if (category) params.set('category', category)
  if (pageInput !== undefined) params.set('page', String(page))
  if (perPageInput !== undefined) params.set('per_page', String(perPage))
  const upstream = `${String(config.phpOrigin).replace(/\/$/, '')}/api/pts_v1_catalog.php${params.toString() ? `?${params.toString()}` : ''}`
  let payload: unknown
  try {
    payload = await $fetch<unknown>(upstream, { timeout: 5000 })
  } catch {
    throw createError({ statusCode: 502, statusMessage: 'PHP catalog data source unavailable' })
  }
  if (!isPublicCatalogPayload(payload)) throw createError({ statusCode: 502, statusMessage: 'Invalid PHP catalog data contract' })
  setHeader(event, 'Cache-Control', 'private, max-age=60, stale-while-revalidate=60')
  return payload
})
