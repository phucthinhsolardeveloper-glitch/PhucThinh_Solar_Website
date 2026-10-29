import { editorialDetailPublicPath, editorialPublicRouteMode, isSafeEditorialSlug, phpEditorialDetailUrl, POLICY_PUBLIC_ROOT } from '#shared/editorial-public-route'

export default defineNuxtRouteMiddleware((to) => {
  const config = useRuntimeConfig()
  const slug = to.params.slug
  if (!isSafeEditorialSlug(slug)) throw createError({ statusCode: 404, statusMessage: 'Policy not found' })
  if (editorialPublicRouteMode(config.public.policyDetailPublicRouteEnabled, config.public.policyDetailPublicRouteIndexable).renderNuxt) {
    const canonicalPath = editorialDetailPublicPath(POLICY_PUBLIC_ROOT, slug)
    if (to.path !== canonicalPath) return navigateTo(canonicalPath, { redirectCode: 301 })
    return
  }
  const target = phpEditorialDetailUrl(config.public.phpPublicOrigin, POLICY_PUBLIC_ROOT, slug)
  return navigateTo(target, { external: true, redirectCode: 307 })
})
