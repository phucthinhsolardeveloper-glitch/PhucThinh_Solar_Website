import { PROJECTS_PUBLIC_PATH, phpPublicRouteUrl } from '#shared/public-route-gate'
import { editorialPublicRouteMode } from '#shared/editorial-public-route'
import { legacyProjectsFilterQuery } from '#shared/public-route-query'

export default defineNuxtRouteMiddleware((to) => {
  const config = useRuntimeConfig()
  if (editorialPublicRouteMode(config.public.projectsPublicRouteEnabled, config.public.projectsPublicRouteIndexable).renderNuxt) return
  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, PROJECTS_PUBLIC_PATH, legacyProjectsFilterQuery(to.query)), { external: true, redirectCode: 307 })
})
