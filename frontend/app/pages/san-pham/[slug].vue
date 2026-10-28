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
const { site, payload, extras, product, error, pending } = await useProductPreviewData(slug)

useSeoMeta({
  title: computed(() => product.value?.name || 'Sản phẩm — Phúc Thịnh Solar'),
  description: computed(() => product.value?.summary || ''),
  robots: computed(() => indexable.value ? 'index, follow' : 'noindex, nofollow, noarchive'),
})
</script>
<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <main>
      <div class="mx-auto w-full max-w-[1766px] px-4">
        <SiteBreadcrumb :items="[{ label: 'Sản phẩm', to: '/san-pham/' }, ...(product ? [{ label: product.name }] : [])]" />
      </div>

      <div v-if="pending" class="mx-auto min-h-[45vh] w-full max-w-[1766px] px-4 pt-12 pb-20" role="status" aria-live="polite">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Đang tải sản phẩm…</h1>
        <p class="text-slate-500">Đang lấy dữ liệu sản phẩm từ CMS.</p>
      </div>

      <div v-else-if="error || !product" class="mx-auto min-h-[45vh] w-full max-w-[1766px] px-4 pt-12 pb-20" role="status">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Sản phẩm</h1>
        <p class="text-slate-500">Không thể tải sản phẩm hoặc slug chưa hợp lệ.</p>
      </div>

      <ProductPreviewPage v-else :product="product" :pricing="payload!.pricing" :content="payload!.content" :documents="payload!.documents" :extras="extras" />
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
