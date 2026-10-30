import {
  CATALOG_PUBLIC_PATH,
  productCategoryPublicPath,
  productCategorySegmentsFromPublicPath,
} from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  // Catch-all route params vary between a scalar and an array with this
  // optional repeat segment. Derive the hierarchy from the validated public
  // path so a valid category can never fall through to the catalog root.
  const segments = productCategorySegmentsFromPublicPath(to.path)

  let categoryPath = ''
  try {
    categoryPath = productCategoryPublicPath(segments)
  } catch {
    throw createError({ statusCode: 404, statusMessage: 'Product category not found' })
  }

  if (to.path !== categoryPath) return navigateTo({ path: categoryPath, query: to.query }, { redirectCode: 301 })
})
