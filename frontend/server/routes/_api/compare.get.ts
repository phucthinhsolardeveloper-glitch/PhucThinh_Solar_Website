import { isPublicComparePayload } from '../../../shared/contracts/public-compare'
import { fetchBackendPayload, requireGet } from '../../utils/backend-api'

export default defineEventHandler(async (event) => {
  requireGet(event)
  const query = getQuery(event)
  const ids = typeof query.ids === 'string' ? query.ids : ''
  if (Object.keys(query).some((key) => key !== 'ids') || !/^[1-9][0-9]{0,9}(?:,[1-9][0-9]{0,9}){0,3}$/.test(ids)) throw createError({ statusCode: 400, statusMessage: 'Invalid compare query' })
  const payload = await fetchBackendPayload(event, {
    endpoint: '/api/v1/compare',
    query: { ids },
    validate: isPublicComparePayload,
    invalidMessage: 'Invalid compare data contract',
    unavailableMessage: 'compare data source unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
