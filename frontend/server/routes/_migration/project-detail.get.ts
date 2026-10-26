import { isProjectSlug, isPublicProjectDetailPayload } from '../../../shared/contracts/public-project-detail'

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') { setHeader(event, 'Allow', 'GET'); throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' }) }
  const slug = getQuery(event).slug
  if (typeof slug !== 'string' || !isProjectSlug(slug)) throw createError({ statusCode: 400, statusMessage: 'Invalid project slug' })
  const config = useRuntimeConfig(event)
  try {
    const payload = await $fetch<unknown>(`${String(config.phpOrigin).replace(/\/$/, '')}/api/pts_v1_project_detail.php`, { method: 'GET', query: { slug }, timeout: 5000 })
    if (!isPublicProjectDetailPayload(payload)) throw new Error('invalid contract')
    setHeader(event, 'Cache-Control', 'private, no-store')
    return payload
  } catch (error: any) {
    if (error?.statusCode === 400 || error?.statusCode === 404) throw error
    throw createError({ statusCode: 502, statusMessage: 'PHP project detail data source unavailable' })
  }
})
