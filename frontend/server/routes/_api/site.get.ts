import { isPublicSitePayload } from '../../../shared/contracts/public-site'
import { fetchBackendPayload, requireGet } from '../../utils/backend-api'

/**
 * Internal server-side adapter. Only calls an allowlisted, read-only Laravel URL.
 * No user-supplied upstream URL or request cookies are forwarded.
 */
export default defineEventHandler(async (event) => {
  requireGet(event)
  const payload = await fetchBackendPayload(event, {
    endpoint: '/api/v1/site',
    validate: isPublicSitePayload,
    invalidMessage: 'Invalid site data contract',
    unavailableMessage: 'data source unavailable',
  })
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
