import { isPublicSearchPayload } from '../../../shared/contracts/public-search'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  if (Object.keys(query).some((key) => !['keyword', 'page', 'per_page'].includes(key))) throw createError({ statusCode: 400, statusMessage: 'Invalid search query' })
  const keyword = typeof query.keyword === 'string' ? query.keyword.trim() : ''
  const page = typeof query.page === 'string' ? query.page : undefined
  const perPage = typeof query.per_page === 'string' ? query.per_page : undefined
  if (!keyword || [...keyword].length > 100 || /[\u0000-\u001F\u007F]/.test(keyword)) throw createError({ statusCode: 400, statusMessage: 'Invalid search keyword' })
  if ((query.page !== undefined && (page === undefined || !/^[1-9][0-9]*$/.test(page) || Number(page) > 10000)) || (query.per_page !== undefined && (perPage === undefined || !/^[1-9][0-9]*$/.test(perPage) || Number(perPage) > 48))) throw createError({ statusCode: 400, statusMessage: 'Invalid search pagination' })

  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_search.php',
    query: { keyword, page, per_page: perPage },
    validate: isPublicSearchPayload,
    invalidMessage: 'Invalid PHP search data contract',
    unavailableMessage: 'PHP search data source unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
