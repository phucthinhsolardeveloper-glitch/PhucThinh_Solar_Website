import { isPublicContactPayload } from '../../../shared/contracts/public-contact'

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') {
    setHeader(event, 'Allow', 'GET')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
  if (Object.keys(getQuery(event)).length) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  const config = useRuntimeConfig(event)
  try {
    const payload = await $fetch<unknown>(`${String(config.phpOrigin).replace(/\/$/, '')}/api/pts_v1_contact.php`, { method: 'GET', timeout: 5000 })
    if (!isPublicContactPayload(payload)) throw new Error('invalid contract')
    setHeader(event, 'Cache-Control', 'private, no-store')
    return payload
  } catch (error: any) {
    if (error?.statusCode === 400 || error?.statusCode === 405) throw error
    throw createError({ statusCode: 502, statusMessage: 'PHP contact data source unavailable' })
  }
})
