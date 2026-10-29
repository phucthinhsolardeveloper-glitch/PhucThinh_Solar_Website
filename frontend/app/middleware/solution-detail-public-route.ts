import { SOLUTIONS_PUBLIC_PATH } from '#shared/public-route-gate'
import { editorialDetailPublicPath, editorialPublicRouteMode, isSafeEditorialSlug, phpEditorialDetailUrl } from '#shared/editorial-public-route'

export default defineNuxtRouteMiddleware((to) => {
  const config = useRuntimeConfig()
  const slug = to.params.slug
  if (!isSafeEditorialSlug(slug)) throw createError({ statusCode: 404, statusMessage: 'Solution not found' })
  if (editorialPublicRouteMode(config.public.solutionDetailPublicRouteEnabled, config.public.solutionDetailPublicRouteIndexable).renderNuxt) {
    const canonicalPath = editorialDetailPublicPath(SOLUTIONS_PUBLIC_PATH, slug)
    if (to.path !== canonicalPath) return navigateTo(canonicalPath, { redirectCode: 301 })
    return
  }
  const target = phpEditorialDetailUrl(config.public.phpPublicOrigin, SOLUTIONS_PUBLIC_PATH, slug)
  return navigateTo(target, { external: true, redirectCode: 307 })
})
