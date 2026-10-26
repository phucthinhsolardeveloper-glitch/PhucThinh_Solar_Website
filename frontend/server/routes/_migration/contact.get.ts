import { isPublicContactPayload } from '../../../shared/contracts/public-contact'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  if (Object.keys(getQuery(event)).length) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_contact.php',
    validate: isPublicContactPayload,
    invalidMessage: 'Invalid PHP contact data contract',
    unavailableMessage: 'PHP contact data source unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
