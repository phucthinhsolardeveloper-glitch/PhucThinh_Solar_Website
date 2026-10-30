<script setup lang="ts">
import type { PublicNewsCta } from '#shared/contracts/public-news'

const props = defineProps<{ cta: PublicNewsCta; phoneLabel: string }>()
const links = useMigrationLinks()
const { openConsultation } = useStorefrontModal()
</script>

<template>
  <section class="mt-7">
    <div class="relative isolate grid grid-cols-1 items-center gap-y-5 overflow-hidden bg-navy-800 px-[22px] py-[25px] text-white min-[541px]:grid-cols-[minmax(0,1fr)_auto] min-[541px]:items-center min-[541px]:gap-x-5 min-[541px]:px-6 min-[541px]:py-[26px] max-[800px]:items-start min-[1101px]:grid-cols-[68px_minmax(0,1fr)_auto] min-[1101px]:items-center max-[767px]:-mx-4 max-[767px]:px-4 max-[359px]:-mx-3 max-[359px]:px-3">
      <span class="pointer-events-none absolute inset-0 -z-10 bg-[linear-gradient(180deg,rgba(10,34,81,.96)_0,rgba(10,34,81,.86)_100%)] min-[901px]:bg-[linear-gradient(90deg,rgba(10,34,81,.97)_0,rgba(10,34,81,.9)_38%,rgba(10,34,81,.55)_68%,rgba(10,34,81,.3)_100%)]" aria-hidden="true" />
      <img v-if="cta.image" :src="cta.image" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-50" loading="lazy" decoding="async">
      <span class="hidden h-[68px] w-[68px] items-center justify-center bg-white/10 text-gold-400 min-[1101px]:flex" aria-hidden="true">
        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m7.1 3.5-2.7.6c-.8.2-1.3 1-1.2 1.9 1.3 8.1 7.7 14.5 15.8 15.8.9.1 1.7-.4 1.9-1.2l.6-2.7c.2-.7-.2-1.5-.9-1.8l-3.1-1.3c-.7-.3-1.5-.1-1.9.4l-1.4 1.7a12 12 0 0 1-5.8-5.8l1.7-1.4c.6-.5.7-1.3.4-1.9L8.9 4.4c-.3-.7-1.1-1.1-1.8-.9Z" /><path d="M15 7a5 5 0 0 1 2 2" /><path d="M14 3a9 9 0 0 1 7 7" /></svg>
      </span>
      <div class="min-w-0">
        <h2 class="mb-[9px] text-[22px] font-[850] leading-[1.3] tracking-[-.025em] max-[800px]:text-[20px] max-[800px]:tracking-[-1.05px]">{{ cta.title }}</h2>
        <p v-if="cta.description" class="m-0 leading-[1.6] text-blue-100 max-[800px]:tracking-[-0.2px]">{{ cta.description }}</p>
      </div>
      <div class="flex w-full flex-col items-stretch gap-2.5 border-t border-white/20 pt-[17px] min-[541px]:w-auto min-[541px]:items-center min-[541px]:border-t-0 min-[541px]:border-l min-[541px]:pt-0 min-[541px]:pl-6 min-[1101px]:flex-row min-[1101px]:gap-5 min-[1101px]:justify-end">
        <a v-if="cta.hotline?.tel" class="flex flex-col whitespace-nowrap text-white no-underline leading-[1.66] max-[540px]:text-center" :href="`tel:${cta.hotline.tel}`">
          <small class="text-[11px] text-blue-100">{{ phoneLabel }}</small>
          <strong class="text-[23px] font-bold">{{ cta.hotline.text }}</strong>
        </a>
        <a
          class="inline-flex min-h-[45px] w-full items-center justify-center gap-2.5 rounded-[4px] bg-gold-500 px-[23px] py-3 text-sm font-[850] text-navy-950 no-underline transition-all duration-150 hover:bg-gold-400 active:scale-[0.98] cursor-pointer shadow-xs min-[541px]:w-auto"
          :href="links.generic('/lien-he/#yeu-cau-tu-van')"
          @click.prevent="openConsultation('Kiến thức: ' + cta.title)"
        >
          {{ cta.buttonLabel || 'Nhận tư vấn ngay' }} <span aria-hidden="true">→</span>
        </a>
      </div>
    </div>
  </section>
</template>
