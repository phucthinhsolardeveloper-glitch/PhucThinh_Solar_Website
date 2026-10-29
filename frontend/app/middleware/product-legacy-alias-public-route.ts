import { isRuntimeFlagEnabled, legacyProductDetailPublicPath, phpPublicRouteUrl, productDetailPublicPath } from '#shared/public-route-gate'
import { publicProductAliasDisposition } from '#shared/public-product-alias-route'
import { isPublicRouteResolveSlug } from '#shared/contracts/public-route-resolve'

export default defineNuxtRouteMiddleware(async (to) => {
  const config = useRuntimeConfig()
  const slug = typeof to.params.slug === 'string' ? to.params.slug : ''
  if (!isPublicRouteResolveSlug(slug)) throw createError({ statusCode: 404, statusMessage: 'Product not found' })

  const enabled = isRuntimeFlagEnabled(config.public.productDetailPublicRouteEnabled)
  if (!enabled) {
    const path = legacyProductDetailPublicPath(slug)
    const target = phpPublicRouteUrl(config.public.phpPublicOrigin, path)
    return navigateTo(target, { external: true, redirectCode: 307 })
  }

  const { data, error } = await useFetch<unknown>('/_migration/public-route-resolve', {
    query: { slug },
    key: `legacy-product-alias-${slug}`,
  })
  const errorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
  const disposition = publicProductAliasDisposition(slug, enabled, data.value, errorStatus)

  if (disposition.mode === 'error') {
    throw createError({
      statusCode: disposition.statusCode,
      statusMessage: disposition.statusCode === 404 ? 'Product not found' : 'Product route resolution unavailable',
    })
  }
  if (disposition.mode === 'canonical') {
    const canonicalPath = productDetailPublicPath(slug)
    if (disposition.path !== canonicalPath) throw createError({ statusCode: 502, statusMessage: 'Invalid product route resolution' })
    return navigateTo(canonicalPath, { redirectCode: disposition.statusCode })
  }
})
