<script setup lang="ts">
import type { PublicNewsArticle } from '#shared/contracts/public-news'
import NewsCard from './NewsCard.vue'

defineProps<{ articles: PublicNewsArticle[]; label: string; readLabel: string; readMoreLabel: string }>()
</script>

<template>
  <!--
    Lưới 2x2: ô đầu (lead) chiếm trọn 2 hàng bên trái, hai ô còn lại (secondary)
    xếp dọc bên phải — đúng grid-template-rows 1fr 1fr của CSS gốc.
  -->
  <section v-if="articles.length" class="mb-[30px] grid grid-cols-1 gap-5 nav:grid-cols-2 nav:grid-rows-2" :aria-label="label">
    <div v-for="(article, index) in articles" :key="article.id" :class="index === 0 ? 'nav:row-span-2' : ''">
      <NewsCard
        :article="article"
        :read-label="readLabel"
        :read-more-label="readMoreLabel"
        :variant="index === 0 ? 'lead' : 'secondary'"
      />
    </div>
  </section>
</template>
