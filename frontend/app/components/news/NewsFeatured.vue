<script setup lang="ts">
import type { PublicNewsArticle } from '#shared/contracts/public-news'
defineProps<{ articles: PublicNewsArticle[]; label: string; readLabel: string; readMoreLabel: string }>()
</script>
<template>
  <section v-if="articles.length" class="news-featured" :aria-label="label"><NewsCard v-for="(article, index) in articles" :key="article.id" :article="article" :read-label="readLabel" :read-more-label="readMoreLabel" :class="index === 0 ? 'news-card--lead' : 'news-card--secondary'" /></section>
</template>
