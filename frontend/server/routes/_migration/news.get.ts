import { isPublicNewsPayload } from '../../../shared/contracts/public-news'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

/** Fixed server-only bridge; no request cookies or user-supplied upstream URL are forwarded. */
export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const query = getQuery(event)
  const allowed = new Set(['q', 'p', 'category', 'category_type', 'category_path'])
  if (Object.keys(query).some((key) => !allowed.has(key))) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  const params = new URLSearchParams()
  for (const key of allowed) {
    const value = query[key]
    if (value !== undefined) {
      if (Array.isArray(value) || typeof value !== 'string' || (key !== 'q' && !value.trim())) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
      params.set(key, value)
    }
  }
  if (params.has('p') && !/^[1-9][0-9]*$/.test(params.get('p')!)) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  if (params.has('category') && !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(params.get('category')!)) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  if (params.has('category_type') && !['list', 'cat', 'item', 'sub'].includes(params.get('category_type')!)) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  if (params.has('category_path')) {
    const segments = params.get('category_path')!.replace(/^\/+|\/+$/g, '').split('/')
    if (!params.has('category_type') || segments.length < 1 || segments.length > 4 || segments.some((segment) => !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(segment))) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
    if (params.has('category') && segments.at(-1) !== params.get('category')) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  } else if (params.has('category_type') && !params.has('category')) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_news.php',
    query: Object.fromEntries(params.entries()),
    validate: isPublicNewsPayload,
    invalidMessage: 'Invalid PHP news data contract',
    unavailableMessage: 'PHP news data source unavailable',
    passThroughStatuses: [400, 404],
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
