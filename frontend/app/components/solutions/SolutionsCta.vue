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
  <PublicCtaBanner
    id="ptsContactModal"
    class="mt-[18px] tb:mt-[22px]"
    variant="corporate"
    :image="backgroundImage"
    labelled-by="solutions-contact-title"
  >
    <div>
      <h2 id="solutions-contact-title" class="m-0 mb-[9px] text-[24px] font-extrabold leading-[1.3] tracking-tight text-white max-[520px]:text-[23px]">{{ cta.title }}</h2>
      <p class="m-0 text-base leading-[1.65] text-blue-100/95 max-[520px]:tracking-[-0.01em]">{{ cta.description }}</p>
    </div>
    <template #actions>
    <div class="flex w-full flex-col items-stretch gap-3 min-[901px]:w-auto min-[901px]:flex-row min-[901px]:items-center min-[901px]:gap-6">
      <UButton
        :href="links.generic(cta.links.consult.href === '#ptsContactModal' ? '/lien-he/#yeu-cau-tu-van' : cta.links.consult.href)"
        color="primary"
        size="lg"
        class="min-h-12 justify-center gap-5 rounded-[4px] bg-hdr-gold px-[22px] py-[13px] text-[15px] leading-[1.66] font-extrabold text-navy-950 no-underline hover:bg-hdr-gold-hover"
        @click.prevent="openConsultation()"
      >
        <span>{{ cta.buttonLabel }}</span>
        <span aria-hidden="true">→</span>
      </UButton>
      <a
        v-if="cta.hotline"
        :href="`tel:${cta.hotline.tel}`"
        class="flex w-full flex-col items-center text-center text-white no-underline min-[901px]:w-auto min-[901px]:items-start min-[901px]:text-left"
      >
        <small class="mb-[5px] block text-xs text-blue-100">{{ cta.hotline.label }}</small>
        <strong class="text-xl font-extrabold text-white">{{ cta.hotline.text }}</strong>
      </a>
    </div>
    </template>
  </PublicCtaBanner>
</template>
