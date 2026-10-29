import { DEALER_PUBLIC_PATH, isRuntimeFlagEnabled, phpPublicRouteUrl } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  const config = useRuntimeConfig()
  if (isRuntimeFlagEnabled(config.public.dealerPublicRouteEnabled)) {
    if (to.path !== DEALER_PUBLIC_PATH) return navigateTo(DEALER_PUBLIC_PATH, { redirectCode: 301 })
    return
  }
  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, DEALER_PUBLIC_PATH), {
    external: true,
    redirectCode: 307,
  })
})
