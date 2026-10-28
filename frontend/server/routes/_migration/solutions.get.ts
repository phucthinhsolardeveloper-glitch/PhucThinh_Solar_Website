import { isPublicSolutionsPayload } from '../../../shared/contracts/public-solutions'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

/** Fixed server-only bridge; no request cookies or user-supplied upstream URL are forwarded. */
export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_solutions.php',
    validate: isPublicSolutionsPayload,
    invalidMessage: 'Invalid PHP solutions data contract',
    unavailableMessage: 'PHP solutions data source unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
