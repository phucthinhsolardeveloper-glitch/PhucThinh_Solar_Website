import { productDetailPublicPath } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  const slug = typeof to.params.slug === 'string' ? to.params.slug : ''
  try { productDetailPublicPath(slug) } catch { throw createError({ statusCode: 404, statusMessage: 'Product not found' }) }
})
