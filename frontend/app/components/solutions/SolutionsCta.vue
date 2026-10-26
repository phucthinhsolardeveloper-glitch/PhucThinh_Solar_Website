<script setup lang="ts">
import type { PublicSolutionsCta } from '../../../shared/contracts/public-solutions'
defineProps<{ cta: PublicSolutionsCta }>()
</script>
<template>
  <section id="ptsContactModal" class="solutions-cta" aria-labelledby="solutions-contact-title"><img v-if="cta.image" class="solutions-cta__background" :src="cta.image" alt="" width="1600" height="500" loading="lazy"><div class="solutions-cta__copy"><h2 id="solutions-contact-title">{{ cta.title }}</h2><p>{{ cta.description }}</p></div><div class="solutions-cta__actions"><a class="solutions-button" :href="cta.links.consult.href">{{ cta.buttonLabel }} <span aria-hidden="true">→</span></a><a v-if="cta.hotline" class="solutions-hotline" :href="`tel:${cta.hotline.tel}`"><small>{{ cta.hotline.label }}</small><strong>{{ cta.hotline.text }}</strong></a></div></section>
</template>
