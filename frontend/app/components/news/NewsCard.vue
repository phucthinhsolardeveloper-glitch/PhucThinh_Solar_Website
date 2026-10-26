<script setup lang="ts">
import type { PublicNewsArticle } from '#shared/contracts/public-news'
defineProps<{ article: PublicNewsArticle; readLabel: string; readMoreLabel: string }>()
</script>
<template>
  <article class="news-card">
    <a :href="article.url" class="news-card__link">
      <div class="news-card__media" :class="{ 'is-placeholder': !article.image }"><img v-if="article.image" :src="article.image" :alt="article.title" loading="lazy" decoding="async"><span v-else aria-hidden="true">✦</span><span v-if="article.category" class="news-card__badge">{{ article.category.name }}</span></div>
      <div class="news-card__body"><p v-if="article.date || article.readingMinutes" class="news-card__meta"><time v-if="article.date" :datetime="article.date">{{ article.date.split('-').reverse().join('/') }}</time><span v-if="article.readingMinutes">{{ article.readingMinutes }} {{ readLabel }}</span></p><h2>{{ article.title }}</h2><p v-if="article.description">{{ article.description }}</p><span class="news-card__action">{{ readMoreLabel }} <span aria-hidden="true">→</span></span></div>
    </a>
  </article>
</template>
