import { CONTACT_PUBLIC_PATH, isRuntimeFlagEnabled, phpPublicRouteUrl } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  const config = useRuntimeConfig()
  if (isRuntimeFlagEnabled(config.public.contactPublicRouteEnabled)) {
    if (to.path !== CONTACT_PUBLIC_PATH) return navigateTo(CONTACT_PUBLIC_PATH, { redirectCode: 301 })
    return
  }
  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, CONTACT_PUBLIC_PATH), {
    external: true,
    redirectCode: 307,
  })
})
