import { isPublicSearchPayload } from '../../../shared/contracts/public-search'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/backend-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => !['keyword', 'page', 'per_page', 'brand', 'price', 'sort'].includes(key))) throw createError({ statusCode: 400, statusMessage: 'Invalid search query' })
  const keyword = typeof query.keyword === 'string' ? query.keyword.trim() : ''
  const page = typeof query.page === 'string' ? query.page : undefined
  const perPage = typeof query.per_page === 'string' ? query.per_page : undefined
  const brand = typeof query.brand === 'string' ? query.brand : undefined
  const price = typeof query.price === 'string' ? query.price : undefined
  const sort = typeof query.sort === 'string' ? query.sort : undefined
  if (!keyword || [...keyword].length > 100 || /[\u0000-\u001F\u007F]/.test(keyword)) throw createError({ statusCode: 400, statusMessage: 'Invalid search keyword' })
  if ((query.page !== undefined && (page === undefined || !/^[1-9][0-9]*$/.test(page) || Number(page) > 10000)) || (query.per_page !== undefined && (perPage === undefined || !/^[1-9][0-9]*$/.test(perPage) || Number(perPage) > 48))) throw createError({ statusCode: 400, statusMessage: 'Invalid search pagination' })
  if ((query.brand !== undefined && (brand === undefined || !/^[1-9][0-9]*(?:,[1-9][0-9]*)*$/.test(brand))) || (query.price !== undefined && (price === undefined || !/^[123](?:,[123])*$/.test(price)))) throw createError({ statusCode: 400, statusMessage: 'Invalid search filters' })
  if (query.sort !== undefined && (sort === undefined || !['default', 'abc', 'abc-desc', 'price', 'price-desc', 'new', 'new-desc'].includes(sort))) throw createError({ statusCode: 400, statusMessage: 'Invalid search sort' })

  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/v1/search',
    query: { keyword, page, per_page: perPage, brand, price, sort },
    validate: isPublicSearchPayload,
    invalidMessage: 'Invalid search data contract',
    unavailableMessage: 'search data source unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
