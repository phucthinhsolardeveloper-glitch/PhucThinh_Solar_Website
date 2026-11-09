<script setup lang="ts">
import { layoutDraftQuery } from '#shared/page-layout'
import HomePageContent from '~/components/home/HomePageContent.vue'

// Home with a layout draft from "Bố cục trang", from a signed Admin link.
definePageMeta({ layout: false })
const route = useRoute()
const preview = layoutDraftQuery(route.params.layout, route.query)
useSeoMeta({ robots: 'noindex, nofollow, noarchive' })
if (import.meta.server) {
  useResponseHeader('Cache-Control').value = 'private, no-store'
  useResponseHeader('X-Robots-Tag').value = 'noindex, nofollow, noarchive'
  useResponseHeader('Referrer-Policy').value = 'no-referrer'
  if (!preview) setResponseStatus(403, 'Preview unavailable')
}
</script>

<template>
  <div>
    <div class="sticky top-0 z-[60] border-b border-gold-500/30 bg-amber-50 px-4 py-2 text-center text-sm font-semibold text-navy-800" role="status">
      Bản xem trước bố cục Trang chủ · Chưa xuất bản
    </div>
    <div v-if="!preview" class="pts-container py-20 text-center">
      <h1 class="text-2xl font-bold">Liên kết xem trước không còn hiệu lực</h1>
      <p class="mt-3 text-slate-600">Mở lại "Bố cục trang" trong Admin để lấy liên kết mới.</p>
    </div>
    <HomePageContent v-else :draft="preview" />
  </div>
</template>
