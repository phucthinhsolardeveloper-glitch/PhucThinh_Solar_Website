<script setup lang="ts">
import type { PublicNewsCta } from '#shared/contracts/public-news'
defineProps<{ cta: PublicNewsCta; phoneLabel: string }>()
</script>
<template>
  <section class="news-cta"><img v-if="cta.image" :src="cta.image" alt="" loading="lazy" decoding="async"><div><h2>{{ cta.title }}</h2><p v-if="cta.description">{{ cta.description }}</p><a href="/lien-he/#yeu-cau-tu-van">{{ cta.buttonLabel || 'Nhận tư vấn ngay' }} <span aria-hidden="true">→</span></a><a v-if="cta.hotline?.tel" :href="`tel:${cta.hotline.tel}`">{{ phoneLabel }}: {{ cta.hotline.text }}</a></div></section>
</template>
