import { ABOUT_PUBLIC_PATH, isRuntimeFlagEnabled, phpPublicRouteUrl } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  const config = useRuntimeConfig()
  if (isRuntimeFlagEnabled(config.public.aboutPublicRouteEnabled)) {
    if (to.path !== ABOUT_PUBLIC_PATH) return navigateTo(ABOUT_PUBLIC_PATH, { redirectCode: 301 })
    return
  }
  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, ABOUT_PUBLIC_PATH), {
    external: true,
    redirectCode: 307,
  })
})
