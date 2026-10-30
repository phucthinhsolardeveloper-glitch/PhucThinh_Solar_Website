<script setup lang="ts">
import type { PublicProjectsCta } from '#shared/contracts/public-projects'

const props = defineProps<{ cta: PublicProjectsCta; label: string }>()
const links = useMigrationLinks()
const { openConsultation } = useStorefrontModal()
const backgroundImage = computed(() => {
  const src = props.cta.image
  return /^\/upload\/news\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(src)
    ? `/thumbs/1600x500x1${src}`
    : ''
})
const trustItems = computed(() => props.cta.trust?.length ? props.cta.trust : [
  { label: 'Tư vấn tận tâm', image: '', icon: 'headset' as const },
  { label: 'Giải pháp tối ưu', image: '', icon: 'cogs' as const },
  { label: 'Đồng hành lâu dài', image: '', icon: 'shield' as const },
])
</script>

<template>
  <section class="mt-[38px] min-[901px]:mt-[50px]">
    <div class="relative isolate grid min-h-0 grid-cols-1 items-center gap-6 overflow-hidden bg-[linear-gradient(112deg,#061735_0%,#0b2b59_68%,#123c72_100%)] px-[22px] py-7 text-white min-[701px]:min-h-[214px] min-[701px]:px-6 min-[901px]:grid-cols-[minmax(0,1fr)_minmax(270px,auto)] min-[901px]:gap-[38px] min-[901px]:px-[42px] min-[901px]:py-[34px]">
      <span class="absolute inset-0 -z-20 bg-[linear-gradient(180deg,rgba(10,34,81,.96)_0,rgba(10,34,81,.86)_100%)] min-[901px]:bg-[linear-gradient(90deg,rgba(10,34,81,.97)_0,rgba(10,34,81,.9)_38%,rgba(10,34,81,.55)_68%,rgba(10,34,81,.3)_100%)]" aria-hidden="true" />
      <img v-if="backgroundImage" :src="backgroundImage" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-50" loading="lazy" decoding="async">
      <div class="min-w-0 min-[901px]:max-w-[590px]">
        <p v-if="cta.eyebrow" class="mb-2 text-xs font-extrabold uppercase tracking-[.08em] text-gold-400">{{ cta.eyebrow }}</p>
        <h2 class="mt-0 mb-2 text-[28px] font-black leading-[1.3] tracking-[-.025em]">{{ cta.title }}</h2>
        <p v-if="cta.description" class="mb-0 max-w-[64ch] text-[15.5px] leading-relaxed text-blue-100">{{ cta.description }}</p>
        <ul v-if="trustItems.length" class="mt-5 mb-0 flex list-none flex-wrap gap-x-7 gap-y-5 p-0 text-[13.5px] font-extrabold text-blue-100">
          <li v-for="item in trustItems" :key="item.label" class="flex items-center gap-[10px]">
            <img v-if="item.image" :src="item.image" alt="" class="h-[34px] w-[34px] shrink-0 rounded-[3px] bg-white/10 p-1.5 object-contain" loading="lazy">
            <span v-else class="grid h-[34px] w-[34px] shrink-0 place-items-center rounded-[3px] bg-white/10 text-gold-400">
              <svg v-if="item.icon === 'headset'" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 13v-3a8 8 0 0 1 16 0v3M4 12H2v7h4v-7H4Zm16 0h2v7h-4v-7h2ZM19 19v2h-7" /></svg>
              <svg v-else-if="item.icon === 'cogs'" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-1.7 2.9-.2-.1a1.7 1.7 0 0 0-1.8.1l-.2.1h-3.4l-.1-.2a1.7 1.7 0 0 0-1.6-1l-.2.1-1.7-2.9.2-.2a1.7 1.7 0 0 0 .3-1.7l-.1-.2V9.6l.2-.1a1.7 1.7 0 0 0 .8-1.5l-.1-.2 2.9-1.7.1.2a1.7 1.7 0 0 0 1.7.3l.2-.1h3.4l.1.2a1.7 1.7 0 0 0 1.6 1l.2-.1 1.7 2.9-.2.2a1.7 1.7 0 0 0-.3 1.7l.1.2v2.4Z" /></svg>
              <svg v-else class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z" /><path d="m9 12 2 2 4-4" /></svg>
            </span>
            <span>{{ item.label }}</span>
          </li>
        </ul>
      </div>
      <div class="flex w-full max-w-[330px] flex-col gap-2.5 min-[901px]:min-w-[270px] min-[901px]:max-w-none">
        <a v-if="cta.hotline?.tel" class="flex items-center justify-center gap-3.5 text-white no-underline" :href="`tel:${cta.hotline.tel}`">
          <span class="grid h-12 w-12 shrink-0 place-items-center rounded-[3px] bg-gold-500 text-white" aria-hidden="true">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.02-.24c1.12.37 2.33.56 3.57.56.55 0 1 .45 1 1V20c0 .55-.45 1-1 1C10.61 21 3 13.39 3 4c0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.24.19 2.45.56 3.57a1 1 0 0 1-.25 1.02l-2.19 2.2Z" /></svg>
          </span>
          <span>
            <small class="mb-0.5 block text-[11px] uppercase text-blue-100">Liên hệ ngay</small>
            <strong class="block text-[22px] font-black leading-tight">{{ cta.hotline.text }}</strong>
          </span>
        </a>
        <a
          class="inline-flex min-h-12 items-center justify-center rounded-[4px] bg-gold-500 px-[22px] py-3 text-sm font-extrabold text-navy-950 no-underline transition-colors duration-150 hover:bg-gold-400 cursor-pointer"
          :href="links.generic('/lien-he/#yeu-cau-tu-van')"
          @click.prevent="openConsultation('Dự án: ' + cta.title)"
        >{{ cta.buttonLabel || label }} <span class="ml-2" aria-hidden="true">→</span></a>
      </div>
    </div>
  </section>
</template>
