import { CART_PUBLIC_PATH, isRuntimeFlagEnabled, phpPublicRouteUrl } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  const config = useRuntimeConfig()
  if (isRuntimeFlagEnabled(config.public.cartPublicRouteEnabled)) {
    if (to.path !== CART_PUBLIC_PATH) return navigateTo({ path: CART_PUBLIC_PATH, query: to.query }, { redirectCode: 301 })
    return
  }
  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, CART_PUBLIC_PATH), {
    external: true,
    redirectCode: 307,
  })
})
