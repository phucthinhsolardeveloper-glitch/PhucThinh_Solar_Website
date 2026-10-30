import { CART_PUBLIC_PATH } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  if (to.path !== CART_PUBLIC_PATH) return navigateTo({ path: CART_PUBLIC_PATH, query: to.query }, { redirectCode: 301 })
})
