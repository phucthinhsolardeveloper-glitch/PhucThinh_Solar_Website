import { SOLUTIONS_PUBLIC_PATH } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  // Nuxt groups the detail route beneath this page file; only gate the hub itself.
  if (to.path !== SOLUTIONS_PUBLIC_PATH && to.path !== SOLUTIONS_PUBLIC_PATH.slice(0, -1)) return

  if (to.path !== SOLUTIONS_PUBLIC_PATH) return navigateTo(SOLUTIONS_PUBLIC_PATH, { redirectCode: 301 })
})
