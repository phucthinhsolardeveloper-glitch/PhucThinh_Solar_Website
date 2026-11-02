<script setup lang="ts">
import type { PublicNewsArticle } from '#shared/contracts/public-news'
import NewsCard from './NewsCard.vue'

defineProps<{ articles: PublicNewsArticle[]; label: string; readLabel: string; readMoreLabel: string }>()
</script>

<template>
  <section v-if="articles.length" class="mb-[30px] grid grid-cols-1 gap-5 tb:grid-cols-2 nav:grid-cols-3" :aria-label="label">
    <div v-for="(article, index) in articles" :key="article.id">
      <NewsCard
        :article="article"
        :read-label="readLabel"
        :read-more-label="readMoreLabel"
        :variant="index === 0 ? 'lead' : 'grid'"
      />
    </div>
  </section>
</template>
