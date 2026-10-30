import { CONTACT_PUBLIC_PATH } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  if (to.path !== CONTACT_PUBLIC_PATH) return navigateTo(CONTACT_PUBLIC_PATH, { redirectCode: 301 })
})
