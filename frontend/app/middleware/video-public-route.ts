import { VIDEO_PUBLIC_PATH, phpPublicRouteUrl } from '#shared/public-route-gate'
import { editorialPublicRouteMode } from '#shared/editorial-public-route'
import { legacyMediaPageQuery } from '#shared/public-route-query'

export default defineNuxtRouteMiddleware((to) => {
  const config = useRuntimeConfig()
  if (editorialPublicRouteMode(config.public.videoPublicRouteEnabled, config.public.videoPublicRouteIndexable).renderNuxt) {
    if (to.path !== VIDEO_PUBLIC_PATH) return navigateTo({ path: VIDEO_PUBLIC_PATH, query: to.query }, { redirectCode: 301 })
    return
  }
  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, VIDEO_PUBLIC_PATH, legacyMediaPageQuery(to.query)), { external: true, redirectCode: 307 })
})
