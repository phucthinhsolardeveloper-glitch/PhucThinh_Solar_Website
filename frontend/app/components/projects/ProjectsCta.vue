<script setup lang="ts">
import type { PublicProjectsCta } from '#shared/contracts/public-projects'

const props = defineProps<{ cta: PublicProjectsCta; label: string }>()
const links = useMigrationLinks()
const { openConsultation } = useStorefrontModal()
const trustItems = computed(() => props.cta.trust?.length ? props.cta.trust : [
  { label: 'Tư vấn tận tâm', image: '', icon: 'headset' as const },
  { label: 'Giải pháp tối ưu', image: '', icon: 'cogs' as const },
  { label: 'Đồng hành lâu dài', image: '', icon: 'shield' as const },
])
</script>

<template>
  <section class="mt-16">
    <div class="relative isolate grid min-h-[220px] grid-cols-1 items-center gap-6 overflow-hidden bg-navy-950 px-6 py-7 text-white nav:grid-cols-[minmax(0,1fr)_auto] nav:px-11 nav:py-9">
      <img v-if="cta.image" :src="cta.image" alt="" class="absolute inset-0 -z-20 h-full w-full object-cover" loading="lazy" decoding="async">
      <span class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(6,21,55,.82),rgba(10,34,81,.66))]" aria-hidden="true" />
      <div class="min-w-0">
        <p v-if="cta.eyebrow" class="mb-2 text-xs font-extrabold uppercase tracking-[.08em] text-gold-400">{{ cta.eyebrow }}</p>
        <h2 class="mt-0 mb-2 text-[clamp(22px,3vw,32px)] font-black leading-tight">{{ cta.title }}</h2>
        <p v-if="cta.description" class="mb-4 max-w-[64ch] leading-relaxed text-blue-100">{{ cta.description }}</p>
        <ul v-if="trustItems.length" class="m-0 flex list-none flex-wrap gap-x-5 gap-y-3 p-0 text-sm font-semibold text-blue-100">
          <li v-for="item in trustItems" :key="item.label" class="flex items-center gap-2">
            <img v-if="item.image" :src="item.image" alt="" class="h-[18px] w-[18px] object-contain" loading="lazy">
            <svg v-else-if="item.icon === 'headset'" class="h-[18px] w-[18px] text-gold-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 13v-3a8 8 0 0 1 16 0v3M4 12H2v7h4v-7H4Zm16 0h2v7h-4v-7h2ZM19 19v2h-7" /></svg>
            <svg v-else-if="item.icon === 'cogs'" class="h-[18px] w-[18px] text-gold-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-1.7 2.9-.2-.1a1.7 1.7 0 0 0-1.8.1l-.2.1h-3.4l-.1-.2a1.7 1.7 0 0 0-1.6-1l-.2.1-1.7-2.9.2-.2a1.7 1.7 0 0 0 .3-1.7l-.1-.2V9.6l.2-.1a1.7 1.7 0 0 0 .8-1.5l-.1-.2 2.9-1.7.1.2a1.7 1.7 0 0 0 1.7.3l.2-.1h3.4l.1.2a1.7 1.7 0 0 0 1.6 1l.2-.1 1.7 2.9-.2.2a1.7 1.7 0 0 0-.3 1.7l.1.2v2.4Z" /></svg>
            <svg v-else class="h-[18px] w-[18px] text-gold-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z" /><path d="m9 12 2 2 4-4" /></svg>
            <span>{{ item.label }}</span>
          </li>
        </ul>
      </div>
      <div class="flex flex-wrap items-center gap-4 nav:justify-end">
        <a v-if="cta.hotline?.tel" class="flex flex-col text-white no-underline" :href="`tel:${cta.hotline.tel}`">
          <small class="text-xs text-blue-100">{{ cta.hotline.label }}</small>
          <strong class="text-lg font-black">{{ cta.hotline.text }}</strong>
        </a>
        <a
          class="inline-flex min-h-12 items-center justify-center rounded-[4px] bg-gold-500 px-5 font-extrabold text-navy-950 no-underline transition-all duration-150 hover:bg-gold-400 active:scale-[0.98] cursor-pointer shadow-xs"
          :href="links.generic('/lien-he/#yeu-cau-tu-van')"
          @click.prevent="openConsultation('Dự án: ' + cta.title)"
        >{{ cta.buttonLabel || label }} <span class="ml-2" aria-hidden="true">→</span></a>
      </div>
    </div>
  </section>
</template>
