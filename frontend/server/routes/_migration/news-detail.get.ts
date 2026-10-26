import { isNewsDetailSlug, isPublicNewsDetailPayload } from '../../../shared/contracts/public-news-detail'

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') { setHeader(event, 'Allow', 'GET'); throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' }) }
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'slug') || typeof query.slug !== 'string' || !isNewsDetailSlug(query.slug)) throw createError({ statusCode: 400, statusMessage: 'Invalid news slug' })
  const config = useRuntimeConfig(event)
  try {
    const payload = await $fetch<unknown>(`${String(config.phpOrigin).replace(/\/$/, '')}/api/pts_v1_news_detail.php`, { method: 'GET', query: { slug: query.slug }, timeout: 5000 })
    if (!isPublicNewsDetailPayload(payload)) throw new Error('invalid contract')
    setHeader(event, 'Cache-Control', 'private, no-store')
    return payload
  } catch (error: any) {
    if (error?.statusCode === 400 || error?.statusCode === 404) throw error
    throw createError({ statusCode: 502, statusMessage: 'PHP news detail data source unavailable' })
  }
})
