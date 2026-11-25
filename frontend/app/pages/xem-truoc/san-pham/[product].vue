<script setup lang="ts">
import type { PublicProductDetailPayload } from '#shared/contracts/public-product-detail'
import { isPublicProductDetailPayload } from '#shared/contracts/public-product-detail'
import { productPreviewQuery } from '#shared/product-preview'
import ProductDetailPage from '~/components/product-detail/ProductDetailPage.vue'

// Product page as saved in Admin (also while hidden), from a signed link: 15 minutes from Admin's preview, 7 days from "Link gửi khách".
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
        Bản xem riêng của sản phẩm · Chưa hiện công khai trên website · Liên kết chỉ dùng được trong thời hạn đã cấp
      </div>
    </div>
    <div v-if="!payload" class="pts-container py-20 text-center">
      <h1 class="text-2xl font-bold">Liên kết xem riêng đã hết hạn</h1>
      <p class="mt-3 text-slate-600">Hãy liên hệ Phúc Thịnh Solar để nhận liên kết mới.</p>
    </div>
    <main v-else>
      <ProductDetailPage :key="payload.product.id" :product="payload.product" :breadcrumbs="payload.breadcrumbs ?? []" :pricing="payload.pricing" :content="payload.content" :applications="payload.applications" :buying-guides="payload.buyingGuides" :documents="payload.documents" :extras="null" />
    </main>
  </div>
</template>
