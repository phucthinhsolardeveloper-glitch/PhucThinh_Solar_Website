import type { PublicProductDetailPayload } from '#shared/contracts/public-product-detail'
import { isPublicProductDetailPayload } from '#shared/contracts/public-product-detail'
import type { PublicProductExtrasPayload } from '#shared/contracts/public-product-extras'
import { isPublicProductExtrasPayload } from '#shared/contracts/public-product-extras'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }

/**
 * Tách từ migration-product-preview.vue (xem đó + pages/san-pham/[slug].vue)
 * để dùng chung đúng một logic fetch + JSON-LD cho cả preview noindex và
 * route public thật - không nhân bản.
 */
export async function useProductPreviewData(slug: Ref<string>) {
  const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
  const { data: productData, error, pending } = await useFetch<unknown>('/_migration/product-detail', { query: computed(() => ({ slug: slug.value })), default: () => null })
  const { data: extrasData } = await useFetch<unknown>('/_migration/product-extras', { query: computed(() => ({ slug: slug.value })), default: () => null })
  const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
  const payload = computed<PublicProductDetailPayload | null>(() => isPublicProductDetailPayload(productData.value) ? productData.value : null)
  const extras = computed<PublicProductExtrasPayload | null>(() => isPublicProductExtrasPayload(extrasData.value) ? extrasData.value : null)
  const product = computed(() => payload.value?.product ?? null)

  // Noindex preview page: structured data here is never surfaced to search results,
  // but staying schema-accurate now avoids re-deriving it at public cutover time.
  const requestUrl = useRequestURL()
  const jsonLd = computed(() => {
    const p = product.value
    if (!p) return []
    const siteOrigin = `${requestUrl.protocol}//${requestUrl.host}`
    const canonicalUrl = `${siteOrigin}${p.url}`
    const pricing = payload.value?.pricing
    const productNode: Record<string, unknown> = {
      '@context': 'https://schema.org',
      '@type': 'Product',
      name: p.name,
      url: canonicalUrl,
    }
    if (p.code) productNode.sku = p.code
    if (p.summary) productNode.description = p.summary
    if (p.media.length) productNode.image = p.media.map((path) => `${siteOrigin}${path}`)
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
        { '@type': 'ListItem', position: 1, name: 'Trang chủ', item: siteOrigin },
        { '@type': 'ListItem', position: 2, name: 'Sản phẩm', item: `${siteOrigin}/san-pham/` },
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

  return { site, payload, extras, product, error, pending }
}
