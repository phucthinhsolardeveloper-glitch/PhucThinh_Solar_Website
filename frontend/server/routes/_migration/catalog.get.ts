import { isCatalogSlugChain, isPublicCatalogPayload } from '../../../shared/contracts/public-catalog'
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

const brandOrSubcategoryPattern = /^[1-9][0-9]*(?:,[1-9][0-9]*)*$/
const pricePattern = /^[123](?:,[123])*$/
const sortPattern = /^[a-z-]+$/

function listQueryValue(value: unknown, pattern: RegExp): string | undefined {
  if (typeof value !== 'string') return undefined
  return pattern.test(value) ? value : undefined
}

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  const allowed = new Set(['category', 'page', 'per_page', 'brand', 'price', 'subcategory', 'sort', 'keyword', 'need'])
  if (Object.keys(query).some((key) => !allowed.has(key))) throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })

  const category = oneQueryValue(query.category)
  const pageInput = oneQueryValue(query.page)
  const perPageInput = oneQueryValue(query.per_page)
  if ((query.category !== undefined && category === undefined) || (query.page !== undefined && pageInput === undefined) || (query.per_page !== undefined && perPageInput === undefined)) throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })
  if (category !== undefined && !isCatalogSlugChain(category)) throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })
  const page = positiveQuery(pageInput, 1, 10000)
  const perPage = positiveQuery(perPageInput, 20, 48)
  if (page === null || perPage === null) throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })

  const brand = listQueryValue(query.brand, brandOrSubcategoryPattern)
  if (query.brand !== undefined && brand === undefined) throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })
  const price = listQueryValue(query.price, pricePattern)
  if (query.price !== undefined && price === undefined) throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })
  const subcategory = listQueryValue(query.subcategory, brandOrSubcategoryPattern)
  if (query.subcategory !== undefined && subcategory === undefined) throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })
  const sort = listQueryValue(query.sort, sortPattern)
  if (query.sort !== undefined && sort === undefined) throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })
  const keyword = oneQueryValue(query.keyword)
  if (query.keyword !== undefined && (keyword === undefined || [...keyword].length > 100 || keyword.trim() === '')) throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })
  const need = listQueryValue(query.need, /^(?:du-an-cong-trinh|chieu-sang-gia-dinh|san-vuon-canh-quan)(?:,(?:du-an-cong-trinh|chieu-sang-gia-dinh|san-vuon-canh-quan))*$/)
  if (query.need !== undefined && need === undefined) throw createError({ statusCode: 400, statusMessage: 'BAD_QUERY' })

  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_catalog.php',
    query: {
      category,
      page: pageInput === undefined ? undefined : String(page),
      per_page: perPageInput === undefined ? undefined : String(perPage),
      brand,
      price,
      subcategory,
      sort,
      keyword,
      is_need: need,
    },
    validate: isPublicCatalogPayload,
    invalidMessage: 'Invalid PHP catalog data contract',
    unavailableMessage: 'PHP catalog data source unavailable',
    passThroughStatuses: [404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
