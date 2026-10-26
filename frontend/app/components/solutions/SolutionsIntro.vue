<script setup lang="ts">
import type { PublicSolutionsIntro } from '../../../shared/contracts/public-solutions'
defineProps<{ intro: PublicSolutionsIntro }>()
</script>
<template>
  <section class="solutions-intro" aria-labelledby="solutions-title">
    <div class="solutions-intro__grid">
      <figure v-if="intro.image" class="solutions-intro__media"><img :src="intro.image" :alt="intro.title" width="1200" height="750" fetchpriority="high"></figure>
      <div class="solutions-intro__copy"><p class="solutions-eyebrow">{{ intro.eyebrow }}</p><h1 id="solutions-title">{{ intro.title }}</h1><p class="solutions-intro__description">{{ intro.description }}</p><div class="solutions-actions"><a class="solutions-button" :href="intro.links.consult.href">{{ intro.links.consult.label }} <span aria-hidden="true">→</span></a><a class="solutions-text-link" :href="intro.links.explore.href">{{ intro.links.explore.label }}</a></div></div>
    </div>
  </section>
</template>
