<script setup lang="ts">
import type { PublicProductDetailPayload } from '#shared/contracts/public-product-detail'
import { isPublicProductDetailPayload } from '#shared/contracts/public-product-detail'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import ProductPreviewPage from '~/components/product-preview/ProductPreviewPage.vue'

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const route = useRoute()
const slug = computed(() => typeof route.query.slug === 'string' ? route.query.slug : '')
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data: productData, error, pending } = await useFetch<unknown>('/_migration/product-detail', { query: computed(() => ({ slug: slug.value })), default: () => null })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const payload = computed<PublicProductDetailPayload | null>(() => isPublicProductDetailPayload(productData.value) ? productData.value : null)
const product = computed(() => payload.value?.product ?? null)
useSeoMeta({ title: computed(() => product.value ? `${product.value.name} — preview` : 'Product preview'), robots: 'noindex, nofollow, noarchive' })
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

      <ProductPreviewPage v-else :product="product" :pricing="payload!.pricing" :content="payload!.content" :documents="payload!.documents" />
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
