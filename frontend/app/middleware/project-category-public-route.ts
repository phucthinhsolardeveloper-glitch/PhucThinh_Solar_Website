import { isRuntimeFlagEnabled, phpPublicRouteUrl, projectCategoryPublicPath } from '#shared/public-route-gate'
import { legacyProjectCategoryQuery } from '#shared/project-category-route'

export default defineNuxtRouteMiddleware((to) => {
  const config = useRuntimeConfig()
  const slug = typeof to.params.slug === 'string' ? to.params.slug : ''
  let canonicalPath = ''
  try {
    canonicalPath = projectCategoryPublicPath(slug)
  } catch {
    throw createError({ statusCode: 404, statusMessage: 'Project category not found' })
  }

  if (to.path !== canonicalPath) return navigateTo({ path: canonicalPath, query: to.query }, { redirectCode: 301 })
  if (isRuntimeFlagEnabled(config.public.projectsPublicRouteEnabled)) return

  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, canonicalPath, legacyProjectCategoryQuery(to.query)), {
    external: true,
    redirectCode: 307,
  })
})
