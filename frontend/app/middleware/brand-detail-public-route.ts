import { brandDetailPublicPath, isRuntimeFlagEnabled, legacyCatalogFilterQuery, phpPublicRouteUrl } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  const config = useRuntimeConfig()
  const slug = typeof to.params.slug === 'string' ? to.params.slug : ''
  let targetPath: string
  try { targetPath = brandDetailPublicPath(slug) } catch { throw createError({ statusCode: 404, statusMessage: 'Brand not found' }) }
  if (isRuntimeFlagEnabled(config.public.brandDetailPublicRouteEnabled)) {
    if (to.path !== targetPath) return navigateTo(targetPath, { redirectCode: 301 })
    return
  }
  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, targetPath, legacyCatalogFilterQuery({
    page: to.query.page,
    sort: to.query.sort,
    price: to.query.price,
  })), {
    external: true,
    redirectCode: 307,
  })
})
