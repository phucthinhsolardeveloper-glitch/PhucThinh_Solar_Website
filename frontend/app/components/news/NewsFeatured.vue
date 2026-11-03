<script setup lang="ts">
import type { PublicNewsArticle } from '#shared/contracts/public-news'
import NewsCard from './NewsCard.vue'

defineProps<{ articles: PublicNewsArticle[]; label: string; readLabel: string; readMoreLabel: string }>()
</script>

<template>
  <!-- The lead story anchors the spread; supporting articles remain in reading order. -->
  <section v-if="articles.length" class="featured mb-[clamp(36px,4vw,56px)]" :class="{ 'is-solo': articles.length === 1 }" :aria-label="label">
    <div class="featured-lead">
      <NewsCard :article="articles[0]!" :read-label="readLabel" :read-more-label="readMoreLabel" variant="lead" />
    </div>
    <div v-if="articles.length > 1" class="featured-aside">
      <div v-for="article in articles.slice(1, 3)" :key="article.id" class="featured-side">
        <NewsCard :article="article" :read-label="readLabel" :read-more-label="readMoreLabel" variant="secondary" />
      </div>
    </div>
  </section>
</template>

<style scoped>
.featured {
  display: grid;
  grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr);
  align-items: start;
  gap: clamp(20px, 2vw, 32px) clamp(24px, 3vw, 48px);
}
.featured.is-solo { grid-template-columns: minmax(0, 1fr); }
.featured-lead { min-width: 0; }
.featured-aside { display: flex; min-width: 0; flex-direction: column; gap: clamp(20px, 2vw, 32px); }
.featured-side { min-width: 0; padding-bottom: clamp(18px, 2vw, 28px);  }
.featured-side:last-child { border-bottom: 0; padding-bottom: 0; }
.featured-side :deep(.line-clamp-3) { font-size: 17px; line-height: 1.6; }
.featured-lead :deep(h2) { font-size: clamp(24px, 2.2vw, 34px); font-weight: 900; letter-spacing: -0.02em; }

.featured-lead :deep(article) { height: 100%; }
.featured-lead :deep(article > a) { position: relative; isolation: isolate; background: var(--color-navy-950); color: white; }
.featured-lead :deep(article > a > div:first-child) { border-radius: 0; }
.featured-lead :deep(article > a > div:last-child) { padding: clamp(24px, 3vw, 48px); }
.featured-lead :deep(h2) { color: white; font-size: clamp(28px, 3vw, 44px); line-height: 1.2; }
.featured-lead :deep(p) { color: rgb(255 255 255 / .8); }
.featured-lead :deep(a:focus-visible) { outline: 3px solid var(--color-gold-500); outline-offset: 5px; }
@media (max-width: 960px) {
  .featured { grid-template-columns: minmax(0, 1fr); }
}
</style>
