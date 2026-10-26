<script setup lang="ts">
import type { PublicContactPayload } from '#shared/contracts/public-contact'
defineProps<{ contact: PublicContactPayload }>()
</script>
<template>
  <section class="contact-preview__hero" aria-labelledby="contact-preview-title">
    <div v-if="contact.visual" class="contact-preview__visual"><img :src="contact.visual.image" :alt="contact.visual.name" width="1400" height="875"><span class="contact-preview__hotline" v-if="contact.contact.hotline">Hotline hỗ trợ <strong>{{ contact.contact.hotline }}</strong></span></div>
    <div class="contact-preview__hero-copy"><h1 id="contact-preview-title">{{ contact.visual?.name || contact.title }}</h1><p v-if="contact.visual?.description">{{ contact.visual.description }}</p><p v-if="contact.contact.company">{{ contact.contact.company }}</p><a class="contact-preview__action" href="/lien-he/">{{ contact.labels.formCta }} <span aria-hidden="true">→</span></a></div>
  </section>
</template>
