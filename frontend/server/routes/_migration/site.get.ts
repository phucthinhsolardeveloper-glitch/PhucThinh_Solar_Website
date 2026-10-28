import { isPublicSitePayload } from '../../../shared/contracts/public-site'
import { fetchMigrationPayload, requireMigrationGet } from '../../utils/migration-api'

/**
 * Internal server-side adapter. Only calls an allowlisted, read-only PHP URL.
 * No user-supplied upstream URL or request cookies are forwarded.
 */
export default defineEventHandler(async (event) => {
  requireMigrationGet(event)
  const payload = await fetchMigrationPayload(event, {
    endpoint: '/api/pts_v1_site.php',
    validate: isPublicSitePayload,
    invalidMessage: 'Invalid PHP site data contract',
    unavailableMessage: 'PHP data source unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
