import { isPublicProjectsPayload } from '../../../shared/contracts/public-projects'

export default defineEventHandler(async (event) => {
  if (getMethod(event) !== 'GET') {
    setHeader(event, 'Allow', 'GET')
    throw createError({ statusCode: 405, statusMessage: 'Method Not Allowed' })
  }
  const query = getQuery(event)
  const allowed = new Set(['category', 'page', 'per_page'])
  if (Object.keys(query).some((key) => !allowed.has(key))) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
  const config = useRuntimeConfig(event)
  const upstream = `${String(config.phpOrigin).replace(/\/$/, '')}/api/pts_v1_projects.php`
  const params = new URLSearchParams()
  for (const key of allowed) {
    const value = query[key]
    if (value !== undefined) {
      if (Array.isArray(value) || typeof value !== 'string' || !value.trim()) throw createError({ statusCode: 400, statusMessage: 'Bad Query' })
      params.set(key, value)
    }
  }
  try {
    const payload = await $fetch<unknown>(`${upstream}${params.size ? `?${params}` : ''}`, { method: 'GET', timeout: 5000 })
    if (!isPublicProjectsPayload(payload)) throw new Error('invalid contract')
    setHeader(event, 'Cache-Control', 'private, no-store')
    return payload
  } catch (error: any) {
    if (error?.statusCode === 400 || error?.statusCode === 404) throw error
    throw createError({ statusCode: 502, statusMessage: 'PHP projects data source unavailable' })
  }
})
