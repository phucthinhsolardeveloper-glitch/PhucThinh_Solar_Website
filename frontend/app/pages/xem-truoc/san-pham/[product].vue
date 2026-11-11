<script setup lang="ts">
import type { PublicProductDetailPayload } from '#shared/contracts/public-product-detail'
import { isPublicProductDetailPayload } from '#shared/contracts/public-product-detail'
import { productPreviewQuery } from '#shared/product-preview'
import ProductDetailPage from '~/components/product-detail/ProductDetailPage.vue'

// Product page as saved in Admin (also while hidden), from a signed 15-minute link.
definePageMeta({ layout: false })
const route = useRoute()
const preview = productPreviewQuery(route.params.product, route.query)
const { data, error } = await useFetch<unknown>('/_api/product-preview', {
  query: preview ?? {},
  immediate: !!preview,
  default: () => null,
})
const payload = computed<PublicProductDetailPayload | null>(() => isPublicProductDetailPayload(data.value) ? data.value : null)

useSeoMeta({ title: computed(() => 'Xem trước: ' + (payload.value?.product.name ?? 'sản phẩm')), robots: 'noindex, nofollow, noarchive' })
if (import.meta.server) {
  useResponseHeader('Cache-Control').value = 'private, no-store'
  useResponseHeader('X-Robots-Tag').value = 'noindex, nofollow, noarchive'
  useResponseHeader('Referrer-Policy').value = 'no-referrer'
  if (!preview || error.value) setResponseStatus(403, 'Preview unavailable')
}
</script>

<template>
  <div class="text-navy-800">
    <div class="pts-container pt-4">
      <div class="rounded-md border border-gold-500/30 bg-amber-50 px-5 py-3 text-sm font-semibold text-navy-800" role="status">
        Bản xem trước sản phẩm · Theo bản đã lưu trong Admin, kể cả khi đang ẩn · Liên kết dùng được 15 phút
      </div>
    </div>
    <div v-if="!payload" class="pts-container py-20 text-center">
      <h1 class="text-2xl font-bold">Liên kết xem trước không còn hiệu lực</h1>
      <p class="mt-3 text-slate-600">Mở lại sản phẩm trong Admin và bấm “Xem trước” để lấy liên kết mới.</p>
    </div>
    <main v-else>
      <ProductDetailPage :key="payload.product.id" :product="payload.product" :breadcrumbs="payload.breadcrumbs ?? []" :pricing="payload.pricing" :content="payload.content" :applications="payload.applications" :buying-guides="payload.buyingGuides" :documents="payload.documents" :extras="null" />
    </main>
  </div>
</template>
