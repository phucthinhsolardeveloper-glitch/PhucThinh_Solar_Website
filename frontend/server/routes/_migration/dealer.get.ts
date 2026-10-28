import { isPublicDealerPayload } from '../../../shared/contracts/public-dealer'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  if (Object.keys(getQuery(event)).length) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_dealer.php',
    validate: isPublicDealerPayload,
    invalidMessage: 'Invalid PHP dealer data contract',
    unavailableMessage: 'PHP dealer data source unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
