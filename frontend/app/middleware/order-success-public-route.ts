import { ORDER_SUCCESS_PUBLIC_PATH } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  if (to.path !== ORDER_SUCCESS_PUBLIC_PATH) return navigateTo({ path: ORDER_SUCCESS_PUBLIC_PATH, query: to.query }, { redirectCode: 301 })
})
