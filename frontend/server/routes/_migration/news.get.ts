import { isPublicNewsPayload } from '../../../shared/contracts/public-news'

/** Fixed server-only bridge; no request cookies or user-supplied upstream URL are forwarded. */
export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') {
    setHeader(event, 'Allow', 'GET')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
  const query = getQuery(event)
  const allowed = new Set(['q', 'p', 'category'])
  if (Object.keys(query).some((key) => !allowed.has(key))) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  const params = new URLSearchParams()
  for (const key of allowed) {
    const value = query[key]
    if (value !== undefined) {
      if (Array.isArray(value) || typeof value !== 'string' || (key !== 'q' && !value.trim())) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
      params.set(key, value)
    }
  }
  if (params.has('p') && !/^[1-9][0-9]*$/.test(params.get('p')!)) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  if (params.has('category') && !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(params.get('category')!)) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  const config = useRuntimeConfig(event)
  const upstream = `${String(config.phpOrigin).replace(/\/$/, '')}/api/pts_v1_news.php`
  try {
    const payload = await $fetch<unknown>(`${upstream}${params.size ? `?${params}` : ''}`, { method: 'GET', timeout: 5000 })
    if (!isPublicNewsPayload(payload)) throw new Error('invalid contract')
    setHeader(event, 'Cache-Control', 'private, no-store')
    return payload
  } catch (error: any) {
    if (error?.statusCode === 400 || error?.statusCode === 404) throw error
    throw createError({ statusCode: 502, statusMessage: 'PHP news data source unavailable' })
  }
})
