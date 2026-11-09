<script setup lang="ts">
import { isPublicAboutPayload } from '#shared/contracts/public-about'
import { layoutDraftQuery } from '#shared/page-layout'
import AboutPage from '~/components/about/AboutPage.vue'

// About with a layout draft from "Bố cục trang", from a signed Admin link.
const route = useRoute()
const preview = layoutDraftQuery(route.params.layout, route.query)
const { data } = await useAsyncData<unknown>(`about-layout-preview-${preview?.layout ?? 0}`,
  () => preview ? $fetch('/_api/page-layout-draft', { query: preview }) : Promise.resolve(null), { default: () => null })
const about = computed(() => isPublicAboutPayload(data.value) ? data.value : null)
useSeoMeta({ title: 'Xem trước bố cục Giới thiệu', robots: 'noindex, nofollow, noarchive' })
if (import.meta.server) {
  useResponseHeader('Cache-Control').value = 'private, no-store'
  useResponseHeader('X-Robots-Tag').value = 'noindex, nofollow, noarchive'
  useResponseHeader('Referrer-Policy').value = 'no-referrer'
  if (!about.value) setResponseStatus(403, 'Preview unavailable')
}
</script>

<template>
  <main class="min-w-0 bg-white">
    <div class="pts-container pt-4">
      <div class="rounded-md border border-gold-500/30 bg-amber-50 px-5 py-3 text-sm font-semibold text-navy-800" role="status">
        Bản xem trước bố cục Giới thiệu · Chưa xuất bản
      </div>
    </div>
    <div v-if="!about" class="pts-container py-20 text-center">
      <h1 class="text-2xl font-bold">Liên kết xem trước không còn hiệu lực</h1>
      <p class="mt-3 text-slate-600">Mở lại "Bố cục trang" trong Admin để lấy liên kết mới.</p>
    </div>
    <AboutPage v-else :about="about" />
  </main>
</template>
