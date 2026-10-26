import { isPublicAboutPayload } from '../../../shared/contracts/public-about'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_about.php',
    validate: isPublicAboutPayload,
    invalidMessage: 'Invalid PHP About data contract',
    unavailableMessage: 'PHP About data source unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
