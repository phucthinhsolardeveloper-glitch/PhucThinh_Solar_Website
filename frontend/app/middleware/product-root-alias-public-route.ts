import { CATALOG_PUBLIC_PATH, isRuntimeFlagEnabled, LEGACY_CATALOG_PUBLIC_PATH, phpPublicRouteUrl } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware(() => {
  const config = useRuntimeConfig()
  if (isRuntimeFlagEnabled(config.public.catalogPublicRouteEnabled)) {
    return navigateTo(CATALOG_PUBLIC_PATH, { redirectCode: 301 })
  }

  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, LEGACY_CATALOG_PUBLIC_PATH), {
    external: true,
    redirectCode: 307,
  })
})
