import { editorialDetailPublicPath, isSafeEditorialSlug, POLICY_PUBLIC_ROOT } from '#shared/editorial-public-route'

export default defineNuxtRouteMiddleware((to) => {
  const slug = to.params.slug
  if (!isSafeEditorialSlug(slug)) throw createError({ statusCode: 404, statusMessage: 'Policy not found' })
  const canonicalPath = editorialDetailPublicPath(POLICY_PUBLIC_ROOT, slug)
  if (to.path !== canonicalPath) return navigateTo(canonicalPath, { redirectCode: 301 })
})
