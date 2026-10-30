import { BRAND_ROOT_PUBLIC_PATH } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  if (to.path !== BRAND_ROOT_PUBLIC_PATH) return navigateTo({ path: BRAND_ROOT_PUBLIC_PATH, query: to.query }, { redirectCode: 301 })
})
