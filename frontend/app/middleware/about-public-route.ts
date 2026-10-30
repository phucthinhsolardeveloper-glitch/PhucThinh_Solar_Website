import { ABOUT_PUBLIC_PATH } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  if (to.path !== ABOUT_PUBLIC_PATH) return navigateTo(ABOUT_PUBLIC_PATH, { redirectCode: 301 })
})
