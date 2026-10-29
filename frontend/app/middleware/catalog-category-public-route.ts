import {
  CATALOG_PUBLIC_PATH,
  isRuntimeFlagEnabled,
  legacyCatalogFilterQuery,
  phpPublicRouteUrl,
  productCategoryPublicPath,
  productCategorySegmentsFromPublicPath,
} from '#shared/public-route-gate'

export default defineNuxtRouteMiddleware((to) => {
  const config = useRuntimeConfig()
  // Catch-all route params vary between a scalar and an array with this
  // optional repeat segment. Derive the hierarchy from the validated public
  // path so a valid category can never fall through to the catalog root.
  const segments = productCategorySegmentsFromPublicPath(to.path)

  let categoryPath = ''
  try {
    categoryPath = productCategoryPublicPath(segments)
  } catch {
    if (isRuntimeFlagEnabled(config.public.catalogPublicRouteEnabled)) {
      throw createError({ statusCode: 404, statusMessage: 'Product category not found' })
    }
    return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, CATALOG_PUBLIC_PATH, legacyCatalogFilterQuery(to.query)), {
      external: true,
      redirectCode: 307,
    })
  }

  if (isRuntimeFlagEnabled(config.public.catalogPublicRouteEnabled)) {
    if (to.path !== categoryPath) return navigateTo({ path: categoryPath, query: to.query }, { redirectCode: 301 })
    return
  }
  return navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, categoryPath, legacyCatalogFilterQuery(to.query)), {
    external: true,
    redirectCode: 307,
  })
})
