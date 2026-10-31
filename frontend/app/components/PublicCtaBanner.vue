<script setup lang="ts">
import { computed, ref, watch } from 'vue'

const props = withDefaults(defineProps<{
  variant: 'corporate' | 'editorial' | 'catalog'
  image?: string
  labelledBy?: string
}>(), { image: '', labelledBy: undefined })

const fallbackImages = {
  corporate: '/assets/images/site-visuals/cta-corporate-bg.webp',
  editorial: '/assets/images/site-visuals/cta-editorial-bg.webp',
  catalog: '/assets/images/site-visuals/cta-catalog-bg.webp',
} as const

const fallbackImage = computed(() => fallbackImages[props.variant])
const imageSource = ref('')
const imageFailed = ref(false)

watch(() => [props.image, props.variant], () => {
  imageSource.value = props.image || fallbackImage.value
  imageFailed.value = false
}, { immediate: true })

function handleImageError() {
  if (imageSource.value !== fallbackImage.value) {
    imageSource.value = fallbackImage.value
    return
  }
  imageFailed.value = true
}
</script>

<template>
  <section class="public-cta-banner" :class="`public-cta-banner--${variant}`" :aria-labelledby="labelledBy">
    <img v-if="!imageFailed" class="public-cta-banner__image" :src="imageSource" alt="" loading="lazy" decoding="async" @error="handleImageError">
    <span class="public-cta-banner__shade" aria-hidden="true" />
    <div class="public-cta-banner__content"><slot /></div>
    <div v-if="$slots.actions" class="public-cta-banner__actions"><slot name="actions" /></div>
  </section>
</template>

<style scoped>
.public-cta-banner {
  position: relative;
  isolation: isolate;
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 1.5rem;
  overflow: hidden;
  min-height: 172px;
  padding: 1.75rem 2rem;
  border: 1px solid rgb(255 255 255 / 18%);
  border-radius: var(--radius-btn, 4px);
  color: #fff;
  background: #0a2251;
}
.public-cta-banner__image,
.public-cta-banner__shade { position: absolute; inset: 0; z-index: 0; width: 100%; height: 100%; }
.public-cta-banner__image { object-fit: cover; }
.public-cta-banner__shade { background: linear-gradient(90deg, rgb(6 21 55 / 68%) 0%, rgb(10 34 81 / 44%) 48%, rgb(10 34 81 / 24%) 100%); }
.public-cta-banner__content,
.public-cta-banner__actions { position: relative; z-index: 1; min-width: 0; }
.public-cta-banner__content { max-width: 760px; }
.public-cta-banner--corporate { grid-template-columns: minmax(0, 1fr) auto; min-height: 154px; padding: 1.5rem 1.75rem; }
.public-cta-banner--corporate .public-cta-banner__shade { background: linear-gradient(90deg, rgb(6 21 55 / 65%) 0%, rgb(10 34 81 / 42%) 62%, rgb(10 34 81 / 22%) 100%); }
.public-cta-banner--editorial { grid-template-columns: minmax(0, 1fr) minmax(250px, auto); min-height: 214px; padding: 2rem 2.5rem; }
.public-cta-banner--editorial .public-cta-banner__shade { background: linear-gradient(90deg, rgb(6 21 55 / 72%) 0%, rgb(10 34 81 / 46%) 48%, rgb(10 34 81 / 26%) 100%); }
.public-cta-banner--catalog { min-height: 146px; padding: 1.5rem 2rem; }
.public-cta-banner--catalog .public-cta-banner__shade { background: linear-gradient(90deg, rgb(6 21 55 / 62%) 0%, rgb(10 34 81 / 40%) 62%, rgb(10 34 81 / 20%) 100%); }
@media (max-width: 760px) {
  .public-cta-banner,
  .public-cta-banner--corporate,
  .public-cta-banner--editorial,
  .public-cta-banner--catalog { grid-template-columns: minmax(0, 1fr); gap: 1.15rem; min-height: 0; padding: 1.4rem 1.25rem; }
  .public-cta-banner__shade { background: linear-gradient(180deg, rgb(6 21 55 / 58%) 0%, rgb(6 21 55 / 46%) 100%) !important; }
}
</style>
