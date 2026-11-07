import { isPublicContactPayload } from '../../../shared/contracts/public-contact'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/backend-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  if (Object.keys(getQuery(event)).length) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/v1/contact',
    validate: isPublicContactPayload,
    invalidMessage: 'Invalid contact data contract',
    unavailableMessage: 'contact data source unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
