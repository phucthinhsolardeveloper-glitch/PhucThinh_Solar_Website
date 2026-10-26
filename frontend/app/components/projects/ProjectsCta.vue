<script setup lang="ts">
import type { PublicProjectsCta } from '#shared/contracts/public-projects'
defineProps<{ cta: PublicProjectsCta; label: string }>()
</script>
<template>
  <section class="projects-cta"><img v-if="cta.image" :src="cta.image" alt="" loading="lazy" decoding="async"><div><h2>{{ cta.title }}</h2><p v-if="cta.description">{{ cta.description }}</p><a href="/lien-he/#yeu-cau-tu-van">{{ cta.buttonLabel || label }}</a><a v-if="cta.hotline?.tel" :href="`tel:${cta.hotline.tel}`">{{ cta.hotline.text }}</a></div></section>
</template>
