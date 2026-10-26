import { isPublicSolutionDetailPayload, isSolutionSlug } from '../../../shared/contracts/public-solution-detail'

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') { setHeader(event, 'Allow', 'GET'); throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' }) }
  const query = getQuery(event)
  if (Object.keys(query).some((key) => key !== 'slug') || typeof query.slug !== 'string' || !isSolutionSlug(query.slug)) throw createError({ statusCode: 400, statusMessage: 'Invalid solution slug' })
  const config = useRuntimeConfig(event)
  const upstream = `${String(config.phpOrigin).replace(/\/$/, '')}/api/pts_v1_solution_detail.php`
  try {
    const payload = await $fetch<unknown>(upstream, { method: 'GET', query: { slug: query.slug }, timeout: 5000 })
    if (!isPublicSolutionDetailPayload(payload)) throw new Error('invalid contract')
    setHeader(event, 'Cache-Control', 'private, no-store')
    return payload
  } catch (error: any) {
    const status = error?.response?.status || error?.statusCode
    if (status === 404) throw createError({ statusCode: 404, statusMessage: 'Solution not found' })
    throw createError({ statusCode: 502, statusMessage: 'PHP solution detail data source unavailable' })
  }
})
