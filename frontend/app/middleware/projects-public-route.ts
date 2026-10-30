import { PROJECTS_PUBLIC_PATH } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  if (to.path !== PROJECTS_PUBLIC_PATH) return navigateTo({ path: PROJECTS_PUBLIC_PATH, query: to.query }, { redirectCode: 301 })
})
