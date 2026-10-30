import { CART_PUBLIC_PATH, isRuntimeFlagEnabled, phpPublicRouteUrl } from '#shared/public-route-gate'

/** The legacy PHP checkout is part of /gio-hang; Nuxt owns it only behind an explicit flag. */
export default defineNuxtRouteMiddleware(() => {
  const config = useRuntimeConfig()
  if (isRuntimeFlagEnabled(config.public.checkoutPublicRouteEnabled)) return

  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, CART_PUBLIC_PATH), {
    external: true,
    redirectCode: 307,
  })
})
