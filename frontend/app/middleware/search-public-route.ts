import { SEARCH_PUBLIC_PATH } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  if (to.path !== SEARCH_PUBLIC_PATH) return navigateTo({ path: SEARCH_PUBLIC_PATH, query: to.query }, { redirectCode: 301 })
})
