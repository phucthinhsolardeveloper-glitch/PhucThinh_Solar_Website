import { SEARCH_PUBLIC_PATH, isRuntimeFlagEnabled, phpPublicRouteUrl } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware(() => {
  const config = useRuntimeConfig()
  if (isRuntimeFlagEnabled(config.public.searchPublicRouteEnabled)) return
  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, SEARCH_PUBLIC_PATH), {
    external: true,
    redirectCode: 307,
  })
})
