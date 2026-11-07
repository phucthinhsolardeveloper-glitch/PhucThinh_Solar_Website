import { isPublicAboutPayload } from '../../../shared/contracts/public-about'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/backend-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/v1/about',
    validate: isPublicAboutPayload,
    invalidMessage: 'Invalid About data contract',
    unavailableMessage: 'About data source unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
