import { brandDetailPublicPath } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  const slug = typeof to.params.slug === 'string' ? to.params.slug : ''
  let targetPath: string
  try { targetPath = brandDetailPublicPath(slug) } catch { throw createError({ statusCode: 404, statusMessage: 'Brand not found' }) }
  if (to.path !== targetPath) return navigateTo({ path: targetPath, query: to.query }, { redirectCode: 301 })
})
