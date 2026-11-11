<script setup lang="ts">
import { isPublicSeoIndexable, productSeoDescription } from '#shared/public-seo'
import ProductDetailPage from '~/components/product-detail/ProductDetailPage.vue'
definePageMeta({
  layout: false,
  middleware: 'product-detail-public-route',
})

const config = useRuntimeConfig()
const indexable = computed(() => isPublicSeoIndexable(config.public.productDetailPublicRouteIndexable, true))
const route = useRoute()
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : '')
const { site, payload, extras, product, error, pending, ready } = useProductDetailData(slug)
await ready
const productErrorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
if (import.meta.server && productErrorStatus === 404) setResponseStatus(404, 'Not Found')
else if (import.meta.server && (error.value || !product.value)) setResponseStatus(502, 'Bad Gateway')
const productTitle = computed(() => payload.value?.seo.title || product.value?.name || 'Sản phẩm — Phúc Thịnh Solar')
const productDescription = computed(() => productSeoDescription({
  seo: payload.value?.seo.description,
  summary: product.value?.summary,
  name: product.value?.name,
  category: payload.value?.breadcrumbs?.at(-1)?.label,
}))
const productKeywords = computed(() => payload.value?.seo.keywords ?? '')
const breadcrumbItems = computed(() => [
  ...(payload.value?.breadcrumbs ?? [{ label: 'Sản phẩm', url: '/san-pham/' }]).map((item) => ({ label: item.label, to: item.url })),
  ...(product.value ? [{ label: product.value.name }] : []),
])

// GA4 view_item once per product shown (also after an in-app move to another product).
if (import.meta.client) {
  watch(() => product.value?.id, (id) => {
    const p = product.value
    if (!id || !p) return
    const pricing = payload.value?.pricing
    const price = pricing?.mode === 'price' ? (pricing.salePrice ?? pricing.regularPrice ?? undefined) : undefined
    const category = payload.value?.breadcrumbs?.at(-1)?.label
    pushEcommerce('view_item', [{ item_id: String(p.id), item_name: p.name, price, quantity: 1, ...(p.brand ? { item_brand: p.brand } : {}), ...(category ? { item_category: category } : {}) }])
  }, { immediate: true })
}

useSeoMeta({
  title: productTitle,
  description: productDescription,
  robots: computed(() => indexable.value && !!product.value && !error.value ? 'index, follow' : 'noindex, nofollow, noarchive'),
})
useHead({
  meta: computed(() => productKeywords.value ? [{ name: 'keywords', content: productKeywords.value }] : []),
  link: computed(() => product.value?.media?.[0] ? [{ rel: 'preload', as: 'image', href: optimizedImage(product.value.media[0], 960, 960), fetchpriority: 'high' }] : []),
})
usePublicSocialMeta({
  enabled: computed(() => !!product.value && !error.value),
  title: productTitle,
  description: productDescription,
  canonicalPath: computed(() => payload.value?.canonical.url || route.path),
  image: computed(() => product.value?.media?.[0]),
  type: 'article',
})
</script>
<template>
  <div class="text-navy-800">

    <main :aria-busy="pending">
      <div class="border-b border-slate-200/70 bg-slate-50/80">
        <div class="pts-container">
          <SiteBreadcrumb :items="breadcrumbItems" />
        </div>
      </div>

      <div v-if="pending && !product" class="pts-container min-h-[45vh] pt-12 pb-20" role="status" aria-live="polite">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Đang tải sản phẩm…</h1>
        <p class="text-slate-500">Đang tải thông tin sản phẩm…</p>
      </div>

      <div v-else-if="error || !product" class="pts-container min-h-[45vh] pt-12 pb-20" role="status">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Sản phẩm</h1>
        <p class="text-slate-500">Không thể tải sản phẩm hoặc slug chưa hợp lệ.</p>
      </div>

      <ProductDetailPage v-else :key="product.id" :product="product" :breadcrumbs="payload!.breadcrumbs ?? []" :pricing="payload!.pricing" :content="payload!.content" :applications="payload!.applications" :buying-guides="payload!.buyingGuides" :documents="payload!.documents" :extras="extras" />
    </main>

  </div>
</template>
