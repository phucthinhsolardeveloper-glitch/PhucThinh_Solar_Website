<script setup lang="ts">
import SiteHeader from '~/components/SiteHeader.vue'
import SiteFooter from '~/components/SiteFooter.vue'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import type { PublicSitePayload } from '#shared/contracts/public-site'

defineProps<{ error: { statusCode?: number; statusMessage?: string } }>()
const fallback: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data } = await useFetch<unknown>('/_migration/site', { default: () => fallback })
const site = computed(() => isPublicSitePayload(data.value) ? data.value : fallback)
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <SiteHeader :site="site" />
    <main class="mx-auto grid min-h-[55vh] w-full max-w-[1180px] content-center px-4 py-16">
      <p class="mb-2 text-sm font-bold uppercase tracking-wide text-gold-700">{{ error.statusCode || 500 }}</p>
      <h1 class="mb-3 text-[clamp(30px,5vw,46px)] font-black tracking-tight">{{ error.statusCode === 404 ? 'Không tìm thấy trang' : 'Trang chưa thể hiển thị' }}</h1>
      <p class="mb-6 max-w-[60ch] leading-relaxed text-slate-600">Đường dẫn có thể đã thay đổi hoặc nội dung không còn được công bố.</p>
      <div class="flex flex-wrap gap-4"><NuxtLink to="/" class="font-bold text-gold-700">Về trang chủ</NuxtLink><NuxtLink to="/lien-he/" class="font-bold text-navy-800">Liên hệ hỗ trợ</NuxtLink></div>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>
