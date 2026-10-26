import { isPublicSitePayload } from '../../../shared/contracts/public-site'

/**
 * Internal server-side adapter. Only calls an allowlisted, read-only PHP URL.
 * No user-supplied upstream URL or request cookies are forwarded.
 */
export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig(event)
  const upstream = String(config.phpOrigin).replace(/\/$/, '') + '/api/pts_v1_site.php'
  let payload: unknown
  try {
    payload = await $fetch<unknown>(upstream, { timeout: 5000 })
  } catch {
    throw createError({ statusCode: 502, statusMessage: 'PHP data source unavailable' })
  }
  if (!isPublicSitePayload(payload)) {
    throw createError({ statusCode: 502, statusMessage: 'Invalid PHP site data contract' })
  }
  setHeader(event, 'Cache-Control', 'private, no-store')
  return payload
})
