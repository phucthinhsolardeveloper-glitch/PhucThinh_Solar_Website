<script setup lang="ts">
import type { PublicSolutionsCta } from '../../../shared/contracts/public-solutions'

defineProps<{ cta: PublicSolutionsCta }>()
const links = useMigrationLinks()
</script>

<template>
  <section
    id="ptsContactModal"
    class="relative isolate mt-7 flex flex-col items-stretch gap-6 overflow-hidden rounded-btn bg-navy-800 px-[22px] py-[26px] text-white
           nav:flex-row nav:items-center nav:justify-between nav:gap-8 nav:px-9 nav:py-8"
    aria-labelledby="solutions-contact-title"
  >
    <img v-if="cta.image" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-[.12]" :src="cta.image" alt="" width="1600" height="500" loading="lazy">
    <div class="max-w-[610px]">
      <h2 id="solutions-contact-title" class="m-0 mb-2.5 text-2xl font-black text-white">{{ cta.title }}</h2>
      <p class="m-0 text-base leading-relaxed text-blue-100">{{ cta.description }}</p>
    </div>
    <div class="flex flex-none flex-col items-stretch gap-[18px] nav:flex-row nav:items-center nav:gap-6">
      <a class="inline-flex min-h-12 items-center justify-center gap-5 rounded-btn bg-hdr-gold px-[22px] font-extrabold text-navy-950 no-underline transition-colors hover:bg-hdr-gold-hover" :href="links.generic(cta.links.consult.href === '#ptsContactModal' ? '/lien-he/#yeu-cau-tu-van' : cta.links.consult.href)">
        {{ cta.buttonLabel }} <span aria-hidden="true">→</span>
      </a>
      <a v-if="cta.hotline" class="whitespace-nowrap text-center text-white no-underline nav:text-left" :href="`tel:${cta.hotline.tel}`">
        <small class="mb-1.5 block text-xs text-blue-200">{{ cta.hotline.label }}</small>
        <strong class="block text-xl">{{ cta.hotline.text }}</strong>
      </a>
    </div>
  </section>
</template>
