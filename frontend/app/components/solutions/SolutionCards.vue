<script setup lang="ts">
import type { PublicSolutionCard } from '../../../shared/contracts/public-solutions'
defineProps<{ cards: PublicSolutionCard[]; choicesLabel: string; emptyLabel: string; consultLabel: string }>()
</script>
<template>
  <section id="cac-giai-phap" class="solutions-choices" aria-labelledby="solutions-choices-title"><h2 id="solutions-choices-title">{{ cards.length ? choicesLabel : emptyLabel }}</h2><div v-if="cards.length" class="solutions-card-grid"><a v-for="card in cards" :key="card.slug" class="solution-card" :href="card.url"><div class="solution-card__media"><img v-if="card.image" :src="card.image" alt="" width="600" height="375" loading="lazy" decoding="async"><span v-else aria-hidden="true">—</span></div><div class="solution-card__copy"><h3>{{ card.title }}</h3><p v-if="card.description">{{ card.description }}</p><span class="solution-card__action">{{ card.actionLabel }} <span aria-hidden="true">→</span></span></div></a></div><a v-else class="solutions-empty-link" href="#ptsContactModal">{{ consultLabel }} →</a></section>
</template>
