import { SOLUTIONS_PUBLIC_PATH, isRuntimeFlagEnabled, phpPublicRouteUrl } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware(() => {
  const config = useRuntimeConfig()
  if (isRuntimeFlagEnabled(config.public.solutionsPublicRouteEnabled)) return
  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, SOLUTIONS_PUBLIC_PATH), {
    external: true,
    redirectCode: 307,
  })
})
