import { SOLUTIONS_PUBLIC_PATH } from '#shared/public-route-gate'
import { editorialDetailPublicPath, isSafeEditorialSlug, solutionOldPathRedirect } from '#shared/editorial-public-route'

export default defineNuxtRouteMiddleware((to) => {
  const slug = to.params.slug
  if (!isSafeEditorialSlug(slug)) throw createError({ statusCode: 404, statusMessage: 'Solution not found' })
  const legacyRedirect = solutionOldPathRedirect(slug)
  if (legacyRedirect) return navigateTo(legacyRedirect, { redirectCode: 301 })
  const canonicalPath = editorialDetailPublicPath(SOLUTIONS_PUBLIC_PATH, slug)
  if (to.path !== canonicalPath) return navigateTo(canonicalPath, { redirectCode: 301 })
})
