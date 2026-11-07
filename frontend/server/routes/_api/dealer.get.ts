import { isPublicDealerPayload } from '../../../shared/contracts/public-dealer'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/backend-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  if (Object.keys(getQuery(event)).length) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/v1/dealer',
    validate: isPublicDealerPayload,
    invalidMessage: 'Invalid dealer data contract',
    unavailableMessage: 'dealer data source unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
