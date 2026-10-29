<script setup lang="ts">
import ProductPreviewPage from '~/components/product-preview/ProductPreviewPage.vue'

const route = useRoute()
const slug = computed(() => typeof route.query.slug === 'string' ? route.query.slug : '')
const { site, payload, extras, product, error, pending, ready } = useProductPreviewData(slug)
await ready
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
      <div class="border-b border-slate-200/70 bg-slate-50/80">
        <div class="pts-container">
          <SiteBreadcrumb :items="[{ label: 'Sản phẩm preview' }, ...(product ? [{ label: product.name }] : [])]" />
        </div>
      </div>

      <div v-if="pending" class="pts-container min-h-[45vh] pt-12 pb-20" role="status" aria-live="polite">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Đang tải sản phẩm…</h1>
        <p class="text-slate-500">Đang lấy dữ liệu sản phẩm từ CMS.</p>
      </div>

      <div v-else-if="error || !product" class="pts-container min-h-[45vh] pt-12 pb-20" role="status">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Product detail preview</h1>
        <p class="text-slate-500">Không thể tải sản phẩm CMS hoặc slug chưa hợp lệ. Preview không hiển thị dữ liệu giả.</p>
      </div>

      <ProductPreviewPage v-else :product="product" :pricing="payload!.pricing" :content="payload!.content" :applications="payload!.applications" :buying-guides="payload!.buyingGuides" :documents="payload!.documents" :extras="extras" />
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
