import { CATALOG_PUBLIC_PATH } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware(() => {
  return navigateTo(CATALOG_PUBLIC_PATH, { redirectCode: 301 })
})
