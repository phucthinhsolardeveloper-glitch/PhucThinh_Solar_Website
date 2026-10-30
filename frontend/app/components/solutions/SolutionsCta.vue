<script setup lang="ts">
import type { PublicSolutionsCta } from '../../../shared/contracts/public-solutions'

defineProps<{ cta: PublicSolutionsCta }>()
const links = useMigrationLinks()
</script>

<template>
  <section
    id="ptsContactModal"
    class="relative isolate mt-6 flex min-h-[200px] flex-col items-stretch justify-between gap-6 overflow-hidden rounded-btn border border-navy-700/60 bg-navy-900 px-6 py-7 text-white shadow-md
           nav:flex-row nav:items-center nav:gap-10 nav:px-10 nav:py-9"
    aria-labelledby="solutions-contact-title"
  >
    <img v-if="cta.image" class="absolute inset-0 -z-20 h-full w-full object-cover opacity-30" :src="cta.image" alt="" width="1600" height="500" loading="lazy">
    <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-r from-navy-950/95 via-navy-900/90 to-navy-800/70" aria-hidden="true" />
    <div class="max-w-[660px]">
      <h2 id="solutions-contact-title" class="m-0 mb-2.5 text-[clamp(22px,2.2vw,28px)] font-black leading-[1.25] tracking-tight text-white">{{ cta.title }}</h2>
      <p class="m-0 text-[15px] leading-relaxed text-blue-100/95 tb:text-base">{{ cta.description }}</p>
    </div>
    <div class="flex flex-none flex-col items-stretch gap-4 ph:flex-row ph:items-center ph:gap-6">
      <UButton
        :href="links.generic(cta.links.consult.href === '#ptsContactModal' ? '/lien-he/#yeu-cau-tu-van' : cta.links.consult.href)"
        color="primary"
        size="lg"
        class="min-h-12 justify-center gap-3.5 rounded-[4px] bg-hdr-gold px-6 text-sm font-extrabold tracking-wide text-navy-950 no-underline hover:bg-hdr-gold-hover"
      >
        <span>{{ cta.buttonLabel }}</span>
        <span aria-hidden="true">→</span>
      </UButton>
      <UButton
        v-if="cta.hotline"
        :href="`tel:${cta.hotline.tel}`"
        color="neutral"
        variant="outline"
        size="lg"
        class="min-h-12 justify-start rounded-[4px] border-white/15 bg-white/[.04] px-4 py-2 text-white hover:border-gold-400/70 hover:bg-white/[.08] hover:text-white ph:text-left"
      >
        <span class="flex flex-col items-start whitespace-nowrap">
          <small class="text-[11px] font-semibold uppercase tracking-wider text-gold-300">{{ cta.hotline.label }}</small>
          <strong class="text-lg font-extrabold tracking-wide text-white">{{ cta.hotline.text }}</strong>
        </span>
      </UButton>
    </div>
  </section>
</template>
