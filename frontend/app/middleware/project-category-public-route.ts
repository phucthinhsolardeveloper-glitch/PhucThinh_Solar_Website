import { projectCategoryPublicPath } from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  const slug = typeof to.params.slug === 'string' ? to.params.slug : ''
  let canonicalPath = ''
  try {
    canonicalPath = projectCategoryPublicPath(slug)
  } catch {
    throw createError({ statusCode: 404, statusMessage: 'Project category not found' })
  }

  if (to.path !== canonicalPath) return navigateTo({ path: canonicalPath, query: to.query }, { redirectCode: 301 })
})
