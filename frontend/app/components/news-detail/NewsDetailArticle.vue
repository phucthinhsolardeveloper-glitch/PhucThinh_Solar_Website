<script setup lang="ts">
import type { PublicNewsDetailPayload } from '#shared/contracts/public-news-detail'
defineProps<{ article: PublicNewsDetailPayload['article'] }>()
</script>
<template>
  <article class="news-detail-article">
    <h1>{{ article.title }}</h1>
    <p class="news-detail-meta">{{ article.date }} · {{ article.author.name }}</p>
    <img v-if="article.cover" :src="article.cover" :alt="article.title" class="news-detail-cover">
    <p v-if="article.description" class="news-detail-lede">{{ article.description }}</p>
    <div class="news-detail-content"><p v-for="(paragraph, index) in article.content.value.split(/\n+/).filter(Boolean)" :key="index">{{ paragraph }}</p><p v-if="article.content.richTextDeferred" class="news-detail-note">Nội dung đang hiển thị dạng văn bản an toàn trong preview; giữ nguyên rich text sẽ được xử lý ở bước migration tiếp theo.</p></div>
    <div v-if="article.media.gallery.length" class="news-detail-gallery"><img v-for="media in article.media.gallery" :key="media.image" :src="media.image" :alt="media.alt || article.title"></div>
    <ul v-if="article.media.videos.length" class="news-detail-videos"><li v-for="video in article.media.videos" :key="video.url"><a :href="video.url" target="_blank" rel="noopener noreferrer">{{ video.title || 'Xem video' }}</a></li></ul>
  </article>
</template>
