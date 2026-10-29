import { SEARCH_PUBLIC_PATH, isRuntimeFlagEnabled, phpPublicRouteUrl } from '#shared/public-route-gate'
import { legacySearchFilterQuery } from '#shared/public-route-query'

export default defineNuxtRouteMiddleware((to) => {
  const config = useRuntimeConfig()
  if (isRuntimeFlagEnabled(config.public.searchPublicRouteEnabled)) return
  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, SEARCH_PUBLIC_PATH, legacySearchFilterQuery(to.query)), {
    external: true,
    redirectCode: 307,
  })
})
