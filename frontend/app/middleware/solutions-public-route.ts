import { SOLUTIONS_PUBLIC_PATH, isRuntimeFlagEnabled, phpPublicRouteUrl } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  // Nuxt groups the detail route beneath this page file; only gate the hub itself.
  if (to.path !== SOLUTIONS_PUBLIC_PATH && to.path !== SOLUTIONS_PUBLIC_PATH.slice(0, -1)) return

  const config = useRuntimeConfig()
  if (isRuntimeFlagEnabled(config.public.solutionsPublicRouteEnabled)) return
  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, SOLUTIONS_PUBLIC_PATH), {
    external: true,
    redirectCode: 307,
  })
})
