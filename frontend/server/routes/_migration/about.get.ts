import { isPublicAboutPayload } from '../../../shared/contracts/public-about'

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') {
    setHeader(event, 'Allow', 'GET')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
  const config = useRuntimeConfig(event)
  try {
    const payload = await $fetch<unknown>(`${String(config.phpOrigin).replace(/\/$/, '')}/api/pts_v1_about.php`, { method: 'GET', timeout: 5000 })
    if (!isPublicAboutPayload(payload)) throw new Error('invalid contract')
    setHeader(event, 'Cache-Control', 'private, no-store')
    return payload
  } catch {
    throw createError({ statusCode: 502, statusMessage: 'PHP About data source unavailable' })
  }
})
