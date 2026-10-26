<script setup lang="ts">
import type { PublicDealerPayload } from '#shared/contracts/public-dealer'
defineProps<{ dealer: PublicDealerPayload }>()
</script>
<template>
  <section v-if="dealer.overview.title || dealer.overview.description || dealer.overview.products.length" class="dealer-section" id="nang-luc-phan-phoi"><div class="dealer-section__copy"><h2>{{ dealer.overview.title || dealer.labels.overview }}</h2><p v-if="dealer.overview.description">{{ dealer.overview.description }}</p></div><div v-if="dealer.overview.products.length" class="dealer-products"><a v-for="product in dealer.overview.products" :key="product.title + product.image" :href="product.href || undefined" target="_blank" rel="noopener noreferrer"><img v-if="product.image" :src="product.image" :alt="product.title" loading="lazy"><span>{{ product.title }}</span></a></div></section>
  <section v-if="dealer.support.title || dealer.support.items.length" class="dealer-section dealer-section--support" id="ho-tro-dai-ly"><h2>{{ dealer.support.title || dealer.labels.support }}</h2><p v-if="dealer.support.description">{{ dealer.support.description }}</p><div class="dealer-support-grid"><article v-for="item in dealer.support.items" :key="item.title"><img v-if="item.image" :src="item.image" :alt="item.title" loading="lazy"><h3>{{ item.title }}</h3><p>{{ item.description }}</p></article></div></section>
  <section v-if="dealer.testimonials.length" class="dealer-section"><h2>{{ dealer.labels.testimonials }}</h2><div class="dealer-testimonials"><article v-for="item in dealer.testimonials" :key="item.title"><p>“{{ item.quote || item.description }}”</p><strong>{{ item.title }}</strong><small v-if="item.description">{{ item.description }}</small></article></div></section>
  <section v-if="dealer.stats.length" class="dealer-stats" :aria-label="dealer.labels.stats"><div v-for="stat in dealer.stats" :key="stat.label"><strong>{{ stat.value }}+</strong><span>{{ stat.label }}</span></div></section>
</template>
