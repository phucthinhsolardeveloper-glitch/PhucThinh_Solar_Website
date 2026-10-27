import { isPublicSearchPayload } from '../../../shared/contracts/public-search'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  const keyword = typeof query.keyword === 'string' ? query.keyword.trim() : ''
  const page = typeof query.page === 'string' ? query.page : undefined
  const perPage = typeof query.per_page === 'string' ? query.per_page : undefined
  if (!keyword || keyword.length > 100 || /[\u0000-\u001F\u007F]/.test(keyword)) throw createError({ statusCode: 400, statusMessage: 'Invalid search keyword' })
  if (page && !/^[1-9][0-9]*$/.test(page) || perPage && !/^[1-9][0-9]*$/.test(perPage)) throw createError({ statusCode: 400, statusMessage: 'Invalid search pagination' })

  return fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_search.php',
    query: { keyword, page, per_page: perPage },
    validate: isPublicSearchPayload,
    invalidMessage: 'Invalid PHP search data contract',
    unavailableMessage: 'PHP search data source unavailable',
  })
})
