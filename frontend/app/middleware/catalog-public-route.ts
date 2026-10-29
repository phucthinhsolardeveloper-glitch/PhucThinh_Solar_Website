import { CATALOG_PUBLIC_PATH, isRuntimeFlagEnabled, legacyCatalogFilterQuery, phpPublicRouteUrl } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  const config = useRuntimeConfig()
  if (isRuntimeFlagEnabled(config.public.catalogPublicRouteEnabled)) {
    if (to.path !== CATALOG_PUBLIC_PATH) return navigateTo({ path: CATALOG_PUBLIC_PATH, query: to.query }, { redirectCode: 301 })
    return
  }
  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, CATALOG_PUBLIC_PATH, legacyCatalogFilterQuery(to.query)), {
    external: true,
    redirectCode: 307,
  })
})
