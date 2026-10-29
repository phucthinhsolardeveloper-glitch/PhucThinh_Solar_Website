import { brandDetailPublicPath } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  const slug = typeof to.params.slug === 'string' ? to.params.slug : ''
  try { brandDetailPublicPath(slug) } catch { throw createError({ statusCode: 404, statusMessage: 'Brand not found' }) }
})
