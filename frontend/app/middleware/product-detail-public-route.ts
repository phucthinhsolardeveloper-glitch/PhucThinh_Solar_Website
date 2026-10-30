import { productDetailPublicPath } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  const slug = typeof to.params.slug === 'string' ? to.params.slug : ''
  let targetPath: string
  try { targetPath = productDetailPublicPath(slug) } catch { throw createError({ statusCode: 404, statusMessage: 'Product not found' }) }
  if (to.path !== targetPath) return navigateTo(targetPath, { redirectCode: 301 })
})
