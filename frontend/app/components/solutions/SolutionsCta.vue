<script setup lang="ts">
import type { PublicSolutionsCta } from '../../../shared/contracts/public-solutions'
import { useStorefrontModal } from '~/composables/useStorefrontModal'

const props = defineProps<{ cta: PublicSolutionsCta }>()
const links = useMigrationLinks()
const { openConsultation } = useStorefrontModal()
const backgroundImage = computed(() => {
  const src = props.cta.image
  if (/^\/upload\/news\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(src)) {
    return `/thumbs/1600x500x1${src}`
  }
  return /^\/assets\/images\/site-visuals\/catalog-street-lighting\.jpg$/i.test(src) ? src : ''
})
</script>

<template>
  <section
    id="ptsContactModal"
    class="relative isolate mt-[18px] flex flex-col items-stretch justify-between gap-5 overflow-hidden rounded-[3px] bg-navy-800 px-[22px] py-[26px] text-white tb:mt-[22px] min-[901px]:flex-row min-[901px]:items-center min-[901px]:gap-8 min-[901px]:px-9 min-[901px]:py-8"
    aria-labelledby="solutions-contact-title"
  >
    <span class="pointer-events-none absolute inset-0 -z-10 bg-[linear-gradient(180deg,rgba(10,34,81,.96)_0,rgba(10,34,81,.86)_100%)] min-[901px]:bg-[linear-gradient(90deg,rgba(10,34,81,.97)_0,rgba(10,34,81,.9)_38%,rgba(10,34,81,.55)_68%,rgba(10,34,81,.3)_100%)]" aria-hidden="true" />
    <img v-if="backgroundImage" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-50" :src="backgroundImage" alt="" width="1600" height="500" sizes="100vw" loading="lazy" decoding="async">
    <div class="max-w-[660px]">
      <h2 id="solutions-contact-title" class="m-0 mb-2.5 text-[clamp(22px,2.2vw,28px)] font-black leading-[1.25] tracking-tight text-white">{{ cta.title }}</h2>
      <p class="m-0 text-[15px] leading-relaxed text-blue-100/95 tb:text-base">{{ cta.description }}</p>
    </div>
    <div class="flex w-full flex-col items-stretch gap-[18px] min-[901px]:w-auto min-[901px]:flex-row min-[901px]:items-center min-[901px]:gap-6">
      <UButton
        :href="links.generic(cta.links.consult.href === '#ptsContactModal' ? '/lien-he/#yeu-cau-tu-van' : cta.links.consult.href)"
        color="primary"
        size="lg"
        class="min-h-12 justify-center gap-3.5 rounded-[4px] bg-hdr-gold px-6 text-sm font-extrabold tracking-wide text-navy-950 no-underline hover:bg-hdr-gold-hover"
        @click.prevent="openConsultation()"
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
