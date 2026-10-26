<script setup lang="ts">
import type { PublicContactLocation } from '#shared/contracts/public-contact'
defineProps<{ locations: PublicContactLocation[]; title: string }>()
</script>
<template>
  <section class="contact-preview__locations" aria-labelledby="contact-locations-title"><h2 id="contact-locations-title">{{ title }}</h2><div class="contact-preview__locations-grid"><article v-for="location in locations" :key="location.id"><h3>{{ location.name }}</h3><p>{{ location.address }}</p><a v-if="location.mapUrl" :href="location.mapUrl" target="_blank" rel="noopener noreferrer">Xem bản đồ <span aria-hidden="true">→</span></a></article></div></section>
</template>
