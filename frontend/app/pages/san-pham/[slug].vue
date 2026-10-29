<script setup lang="ts">
import { isRuntimeFlagEnabled } from '#shared/public-route-gate'
import ProductPreviewPage from '~/components/product-preview/ProductPreviewPage.vue'
import productDetailPublicRoute from '~/middleware/product-detail-public-route'

definePageMeta({
  layout: false,
  middleware: productDetailPublicRoute,
})

const config = useRuntimeConfig()
const indexable = computed(() => isRuntimeFlagEnabled(config.public.productDetailPublicRouteIndexable))
const route = useRoute()
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : '')
const { site, payload, extras, product, error, pending, ready } = useProductPreviewData(slug)
await ready
const productErrorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
if (import.meta.server && productErrorStatus === 404) setResponseStatus(404, 'Not Found')
else if (import.meta.server && (error.value || !product.value)) setResponseStatus(502, 'Bad Gateway')
const productTitle = computed(() => payload.value?.seo.title || product.value?.name || 'Sản phẩm — Phúc Thịnh Solar')
const productDescription = computed(() => payload.value?.seo.description ?? '')
const productKeywords = computed(() => payload.value?.seo.keywords ?? '')
const breadcrumbItems = computed(() => [
  ...(payload.value?.breadcrumbs ?? [{ label: 'Sản phẩm', url: '/san-pham/' }]).map((item) => ({ label: item.label, to: item.url })),
  ...(product.value ? [{ label: product.value.name }] : []),
])

useSeoMeta({
  title: productTitle,
  description: productDescription,
  robots: computed(() => indexable.value && !!product.value && !error.value ? 'index, follow' : 'noindex, nofollow, noarchive'),
})
useHead({
  meta: computed(() => productKeywords.value ? [{ name: 'keywords', content: productKeywords.value }] : []),
})
usePublicSocialMeta({
  enabled: computed(() => !!product.value && !error.value),
  title: productTitle,
  description: productDescription,
  canonicalPath: computed(() => payload.value?.canonical.url || route.path),
  image: computed(() => product.value?.media?.[0]),
  type: 'product',
})
</script>
<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <main>
      <div class="border-b border-slate-200/70 bg-slate-50/80">
        <div class="pts-container">
          <SiteBreadcrumb :items="breadcrumbItems" />
        </div>
      </div>

      <div v-if="pending" class="pts-container min-h-[45vh] pt-12 pb-20" role="status" aria-live="polite">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Đang tải sản phẩm…</h1>
        <p class="text-slate-500">Đang lấy dữ liệu sản phẩm từ CMS.</p>
      </div>

      <div v-else-if="error || !product" class="pts-container min-h-[45vh] pt-12 pb-20" role="status">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Sản phẩm</h1>
        <p class="text-slate-500">Không thể tải sản phẩm hoặc slug chưa hợp lệ.</p>
      </div>

      <ProductPreviewPage v-else :product="product" :pricing="payload!.pricing" :content="payload!.content" :applications="payload!.applications" :buying-guides="payload!.buyingGuides" :documents="payload!.documents" :extras="extras" />
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
