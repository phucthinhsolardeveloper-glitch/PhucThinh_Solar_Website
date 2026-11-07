import type { PublicProductDetailPayload } from '#shared/contracts/public-product-detail'
import { isPublicProductDetailPayload } from '#shared/contracts/public-product-detail'
import type { PublicProductExtrasPayload } from '#shared/contracts/public-product-extras'
import { isPublicProductExtrasPayload } from '#shared/contracts/public-product-extras'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { canonicalPublicOrigin, canonicalPublicUrl } from '#shared/public-seo'

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }

/**
 * Tách từ migration-product-preview.vue (xem đó + pages/san-pham/[slug].vue)
 * để dùng chung đúng một logic fetch + JSON-LD cho cả preview noindex và
 * route public thật - không nhân bản.
 */
// Not `async`: callers must `await result.ready` themselves from their own
// <script setup> top level. If this function is declared `async` and the
// page does `await useProductPreviewData(slug)`, every composable call
// inside it (useFetch, useRequestURL, useHead, ...) crashes SSR with an
// opaque NUXT_E1001 - the compiler's withAsyncContext transform only
// preserves the active Nuxt/component instance across a *literal* top-level
// `await` in the SFC, not across a call into a separately exported async
// function. Keeping this function synchronous and awaiting its `ready`
// promise directly in the page sidesteps that entirely.
export function useProductPreviewData(slug: Ref<string>) {
  const siteFetch = useFetch<unknown>('/_api/site', { default: () => fallbackSite })
  const productFetch = useFetch<unknown>('/_api/product-detail', { query: computed(() => ({ slug: slug.value })), default: () => null })
  const extrasFetch = useFetch<unknown>('/_api/product-extras', { query: computed(() => ({ slug: slug.value })), default: () => null })
  const { data: siteData } = siteFetch
  const { data: productData, error, pending: productPending } = productFetch
  const { data: extrasData, pending: extrasPending } = extrasFetch
  const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
  const fetchedPayload = computed<PublicProductDetailPayload | null>(() => isPublicProductDetailPayload(productData.value) ? productData.value : null)
  const lastResolvedPayload = shallowRef<PublicProductDetailPayload | null>(null)
  watch(fetchedPayload, (value) => {
    if (value?.product.slug === slug.value) lastResolvedPayload.value = value
  }, { immediate: true })
  const payload = computed<PublicProductDetailPayload | null>(() => {
    if (fetchedPayload.value?.product.slug === slug.value) return fetchedPayload.value
    return productPending.value ? lastResolvedPayload.value : null
  })
  const product = computed(() => payload.value?.product ?? null)
  const fetchedExtras = computed<PublicProductExtrasPayload | null>(() => isPublicProductExtrasPayload(extrasData.value) ? extrasData.value : null)
  const lastResolvedExtras = shallowRef<PublicProductExtrasPayload | null>(null)
  watch([fetchedExtras, fetchedPayload], ([extra, productPayload]) => {
    if (extra && productPayload?.product.slug === slug.value && extra.productId === productPayload.product.id) {
      lastResolvedExtras.value = extra
    }
  }, { immediate: true })
  const extras = computed<PublicProductExtrasPayload | null>(() => {
    if (!product.value) return null
    if (fetchedExtras.value?.productId === product.value.id) return fetchedExtras.value
    return productPending.value && lastResolvedExtras.value?.productId === product.value.id ? lastResolvedExtras.value : null
  })
  const pending = computed(() => productPending.value || extrasPending.value)

  // Noindex preview page: structured data here is never surfaced to search results,
  // but staying schema-accurate now avoids re-deriving it at public cutover time.
  const runtimeConfig = useRuntimeConfig()
  const jsonLd = computed(() => {
    const p = product.value
    const siteOrigin = canonicalPublicOrigin(runtimeConfig.public.canonicalSiteOrigin)
    if (!p || !siteOrigin) return []
    const canonicalUrl = canonicalPublicUrl(siteOrigin, p.url)
    if (!canonicalUrl) return []
    const pricing = payload.value?.pricing
    const productNode: Record<string, unknown> = {
      '@context': 'https://schema.org',
      '@type': 'Product',
      name: p.name,
      url: canonicalUrl,
    }
    if (p.code) productNode.sku = p.code
    if (p.summary) productNode.description = p.summary
    if (p.media.length) {
      const images = p.media.map((path) => canonicalPublicUrl(siteOrigin, path)).filter((url): url is string => Boolean(url))
      if (images.length) productNode.image = images
    }
    if (pricing?.mode === 'price' && (pricing.salePrice ?? pricing.regularPrice)) {
      productNode.offers = {
        '@type': 'Offer',
        url: canonicalUrl,
        priceCurrency: pricing.currency,
        price: pricing.salePrice ?? pricing.regularPrice,
      }
    }
    if (extras.value && extras.value.reviews.total > 0) {
      productNode.aggregateRating = {
        '@type': 'AggregateRating',
        ratingValue: extras.value.reviews.average,
        reviewCount: extras.value.reviews.total,
      }
    }

    const nodes: Record<string, unknown>[] = [productNode, {
      '@context': 'https://schema.org',
      '@type': 'BreadcrumbList',
      itemListElement: [
        { '@type': 'ListItem', position: 1, name: 'Trang chủ', item: canonicalPublicUrl(siteOrigin, '/') },
        { '@type': 'ListItem', position: 2, name: 'Sản phẩm', item: canonicalPublicUrl(siteOrigin, '/san-pham/') },
        { '@type': 'ListItem', position: 3, name: p.name, item: canonicalUrl },
      ],
    }]

    if (payload.value?.content.faqs.length) {
      nodes.push({
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: payload.value.content.faqs.map((faq) => ({
          '@type': 'Question',
          name: faq.question,
          acceptedAnswer: { '@type': 'Answer', text: faq.answer },
        })),
      })
    }
    return nodes
  })
  useHead({
    script: computed(() => jsonLd.value.map((node) => ({
      type: 'application/ld+json',
      innerHTML: JSON.stringify(node).replace(/</g, '\\u003c'),
    }))),
  })

  const ready = Promise.all([siteFetch, productFetch, extrasFetch]).then(() => undefined)

  return { site, payload, extras, product, error, pending, ready }
}
