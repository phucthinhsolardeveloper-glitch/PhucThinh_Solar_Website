import { isPublicSolutionsPayload } from '../../../shared/contracts/public-solutions'

/** Fixed server-only bridge; no request cookies or user-supplied upstream URL are forwarded. */
export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') {
    setHeader(event, 'Allow', 'GET')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
  const config = useRuntimeConfig(event)
  const upstream = `${String(config.phpOrigin).replace(/\/$/, '')}/api/pts_v1_solutions.php`
  try {
    const payload = await $fetch<unknown>(upstream, { method: 'GET', timeout: 5000 })
    if (!isPublicSolutionsPayload(payload)) throw new Error('invalid contract')
    setHeader(event, 'Cache-Control', 'private, no-store')
    return payload
  } catch {
    throw createError({ statusCode: 502, statusMessage: 'PHP solutions data source unavailable' })
  }
})
