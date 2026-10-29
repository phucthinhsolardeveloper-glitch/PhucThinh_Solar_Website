import { NEWS_PUBLIC_PATH, phpPublicRouteUrl } from '#shared/public-route-gate'
import { editorialPublicRouteMode } from '#shared/editorial-public-route'
import { legacyNewsFilterQuery } from '#shared/public-route-query'

export default defineNuxtRouteMiddleware((to) => {
  const config = useRuntimeConfig()
  if (editorialPublicRouteMode(config.public.newsPublicRouteEnabled, config.public.newsPublicRouteIndexable).renderNuxt) return
  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, NEWS_PUBLIC_PATH, legacyNewsFilterQuery(to.query)), { external: true, redirectCode: 307 })
})
