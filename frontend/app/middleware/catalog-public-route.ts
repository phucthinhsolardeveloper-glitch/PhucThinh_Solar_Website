import { CATALOG_PUBLIC_PATH, isRuntimeFlagEnabled, legacyCatalogFilterQuery, phpPublicRouteUrl } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  const config = useRuntimeConfig()
  if (isRuntimeFlagEnabled(config.public.catalogPublicRouteEnabled)) return
  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, CATALOG_PUBLIC_PATH, legacyCatalogFilterQuery(to.query)), {
    external: true,
    redirectCode: 307,
  })
})
