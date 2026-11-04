<script setup lang="ts">
import type { PublicSolutionsCta } from '../../../shared/contracts/public-solutions'

const props = defineProps<{ cta: PublicSolutionsCta; subject?: string }>()
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
    title-id="solutions-contact-title"
    :title="cta.title"
    :description="cta.description"
    :button-label="cta.buttonLabel"
    :hotline="cta.hotline?.text"
    :hotline-label="cta.hotline?.label"
    :subject="subject"
    :fallback-href="cta.links.consult.href === '#ptsContactModal' ? '/lien-he/#yeu-cau-tu-van' : cta.links.consult.href"
  />
</template>
