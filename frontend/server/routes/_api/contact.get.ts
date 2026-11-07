import { isPublicContactPayload } from '../../../shared/contracts/public-contact'
import { fetchBackendPayload, requireGet } from '../../utils/backend-api'

export default defineEventHandler(async (event) => {
  requireGet(event)
  if (Object.keys(getQuery(event)).length) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  const payload = await fetchBackendPayload(event, {
    endpoint: '/api/v1/contact',
    validate: isPublicContactPayload,
    invalidMessage: 'Invalid contact data contract',
    unavailableMessage: 'contact data source unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
