import { isPublicDealerPayload } from '../../../shared/contracts/public-dealer'

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') { setHeader(event, 'Allow', 'GET'); throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' }) }
  if (Object.keys(getQuery(event)).length) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  const config = useRuntimeConfig(event)
  const upstream = `${String(config.phpOrigin).replace(/\/$/, '')}/api/pts_v1_dealer.php`
  try {
    const payload = await $fetch<unknown>(upstream, { method: 'GET', timeout: 5000 })
    if (!isPublicDealerPayload(payload)) throw new Error('invalid contract')
    setHeader(event, 'Cache-Control', 'private, no-store')
    return payload
  } catch { throw createError({ statusCode: 502, statusMessage: 'PHP dealer data source unavailable' }) }
})
