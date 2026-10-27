<script setup lang="ts">
import type { PublicProductDetailPayload } from '#shared/contracts/public-product-detail'
import { isPublicProductDetailPayload } from '#shared/contracts/public-product-detail'
import type { PublicProductExtrasPayload } from '#shared/contracts/public-product-extras'
import { isPublicProductExtrasPayload } from '#shared/contracts/public-product-extras'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import ProductPreviewPage from '~/components/product-preview/ProductPreviewPage.vue'

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const route = useRoute()
const slug = computed(() => typeof route.query.slug === 'string' ? route.query.slug : '')
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data: productData, error, pending } = await useFetch<unknown>('/_migration/product-detail', { query: computed(() => ({ slug: slug.value })), default: () => null })
const { data: extrasData } = await useFetch<unknown>('/_migration/product-extras', { query: computed(() => ({ slug: slug.value })), default: () => null })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const payload = computed<PublicProductDetailPayload | null>(() => isPublicProductDetailPayload(productData.value) ? productData.value : null)
const extras = computed<PublicProductExtrasPayload | null>(() => isPublicProductExtrasPayload(extrasData.value) ? extrasData.value : null)
const product = computed(() => payload.value?.product ?? null)
useSeoMeta({ title: computed(() => product.value ? `${product.value.name} — preview` : 'Product preview'), robots: 'noindex, nofollow, noarchive' })

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
</script>
<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <!--
      Breadcrumb dùng chung khung với nội dung bên dưới (76..1842 ở 1920px) để
      hai khối thẳng mép trái — ảnh production cho thấy breadcrumb bắt đầu đúng
      x=76, cùng mép với cột ảnh sản phẩm.
    -->
    <main>
      <div class="mx-auto w-full max-w-[1766px] px-4">
        <SiteBreadcrumb :items="[{ label: 'Sản phẩm preview' }, ...(product ? [{ label: product.name }] : [])]" />
      </div>

      <div v-if="pending" class="mx-auto min-h-[45vh] w-full max-w-[1766px] px-4 pt-12 pb-20" role="status" aria-live="polite">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Đang tải sản phẩm…</h1>
        <p class="text-slate-500">Đang lấy dữ liệu sản phẩm từ CMS.</p>
      </div>

      <div v-else-if="error || !product" class="mx-auto min-h-[45vh] w-full max-w-[1766px] px-4 pt-12 pb-20" role="status">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Product detail preview</h1>
        <p class="text-slate-500">Không thể tải sản phẩm CMS hoặc slug chưa hợp lệ. Preview không hiển thị dữ liệu giả.</p>
      </div>

      <ProductPreviewPage v-else :product="product" :pricing="payload!.pricing" :content="payload!.content" :documents="payload!.documents" :extras="extras" />
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
