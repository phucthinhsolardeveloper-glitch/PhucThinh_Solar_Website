import { isRuntimeFlagEnabled, phpPublicRouteUrl, productDetailPublicPath } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  const config = useRuntimeConfig()
  if (isRuntimeFlagEnabled(config.public.productDetailPublicRouteEnabled)) return
  const slug = typeof to.params.slug === 'string' ? to.params.slug : ''
  let targetPath = '/san-pham/'
  try { targetPath = productDetailPublicPath(slug) } catch { /* invalid slug: fall back to the catalog landing page */ }
  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, targetPath), {
    external: true,
    redirectCode: 307,
  })
})
