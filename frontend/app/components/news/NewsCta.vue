<script setup lang="ts">
import type { PublicNewsCta } from '#shared/contracts/public-news'

defineProps<{ cta: PublicNewsCta; phoneLabel: string }>()
const links = useMigrationLinks()
</script>

<template>
  <section class="mt-8">
    <div class="relative isolate grid grid-cols-[44px_minmax(0,1fr)] items-center gap-x-4 gap-y-5 overflow-hidden bg-navy-950 px-5 py-5 text-white nav:grid-cols-[52px_minmax(0,1fr)_auto] nav:gap-x-5 nav:px-7 nav:py-6">
      <img v-if="cta.image" :src="cta.image" alt="" class="absolute inset-0 -z-20 h-full w-full object-cover" loading="lazy" decoding="async">
      <span class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(6,21,55,.9),rgba(10,34,81,.78))]" aria-hidden="true" />
      <span class="row-span-2 flex h-11 w-11 items-center justify-center bg-white/10 text-gold-400 nav:row-span-1 nav:h-[52px] nav:w-[52px]" aria-hidden="true">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 10v4a2 2 0 0 0 2 2h2l3 3V5l-3 3H7a2 2 0 0 0-2 2Z" /><path d="M16 9a5 5 0 0 1 0 6m2-9a9 9 0 0 1 0 12" /></svg>
      </span>
      <div class="min-w-0">
        <h2 class="mb-1 text-[clamp(18px,2.2vw,24px)] font-black leading-tight">{{ cta.title }}</h2>
        <p v-if="cta.description" class="m-0 leading-relaxed text-blue-100">{{ cta.description }}</p>
      </div>
      <div class="col-span-2 flex flex-wrap items-center gap-4 nav:col-span-1 nav:justify-end">
        <a v-if="cta.hotline?.tel" class="flex flex-col text-white no-underline" :href="`tel:${cta.hotline.tel}`">
          <small class="text-xs text-blue-100">{{ phoneLabel }}</small>
          <strong class="text-lg font-black">{{ cta.hotline.text }}</strong>
        </a>
        <a class="inline-flex min-h-11 items-center justify-center bg-gold-500 px-4 font-bold text-navy-950 no-underline hover:bg-gold-400" :href="links.generic('/lien-he/#yeu-cau-tu-van')">
          {{ cta.buttonLabel || 'Nhận tư vấn ngay' }} <span class="ml-2" aria-hidden="true">→</span>
        </a>
      </div>
    </div>
  </section>
</template>
