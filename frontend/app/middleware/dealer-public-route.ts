import { DEALER_PUBLIC_PATH } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  if (to.path !== DEALER_PUBLIC_PATH) return navigateTo(DEALER_PUBLIC_PATH, { redirectCode: 301 })
})
