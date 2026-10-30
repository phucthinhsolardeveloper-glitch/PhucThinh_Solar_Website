import { GALLERY_PUBLIC_PATH } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  if (to.path !== GALLERY_PUBLIC_PATH) return navigateTo({ path: GALLERY_PUBLIC_PATH, query: to.query }, { redirectCode: 301 })
})
