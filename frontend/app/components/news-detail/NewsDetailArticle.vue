<script setup lang="ts">
import type { PublicNewsDetailPayload } from '#shared/contracts/public-news-detail'

defineProps<{ article: PublicNewsDetailPayload['article'] }>()
</script>

<template>
  <!--
    Không có ảnh production riêng cho trang chi tiết tin tức trong bộ 13 ảnh
    đã chụp — dựng theo cấu trúc CSS gốc (news-detail-preview.css) và nhất
    quán với PDP/dự án/giải pháp chi tiết đã đo (container 1766px, heading
    navy-800, .cms-content cho nội dung dài).
  -->
  <article>
    <h1 class="mb-4 text-[clamp(28px,3.6vw,42px)] font-black leading-tight text-navy-800">{{ article.title }}</h1>
    <p class="mb-4 text-slate-500">{{ article.date }} · {{ article.author.name }}</p>
    <img v-if="article.cover" :src="article.cover" :alt="article.title" class="my-6 block aspect-video w-full rounded-chip object-contain">
    <p v-if="article.description" class="mb-4 text-lg leading-relaxed text-navy-800">{{ article.description }}</p>

    <!--
      content.value là text thuần (tách bằng \n), KHÔNG phải HTML — không dùng
      v-html, giữ đúng cách xử lý an toàn đã có từ trước khi sanitizer merge.
    -->
    <div class="cms-content">
      <p v-for="(paragraph, index) in article.content.value.split(/\n+/).filter(Boolean)" :key="index">{{ paragraph }}</p>
      <p v-if="article.content.richTextDeferred" class="mt-8 text-sm text-slate-500">
        Nội dung đang hiển thị dạng văn bản an toàn trong preview; giữ nguyên rich text sẽ được xử lý ở bước migration tiếp theo.
      </p>
    </div>

    <div v-if="article.media.gallery.length" class="mt-8 grid grid-cols-1 gap-4 tb:grid-cols-2">
      <img v-for="media in article.media.gallery" :key="media.image" :src="media.image" :alt="media.alt || article.title" class="block h-auto w-full rounded-chip object-contain">
    </div>

    <ul v-if="article.media.videos.length" class="m-0 mt-6 list-none p-0">
      <li v-for="video in article.media.videos" :key="video.url">
        <a class="font-semibold text-gold-700" :href="video.url" target="_blank" rel="noopener noreferrer">{{ video.title || 'Xem video' }}</a>
      </li>
    </ul>
  </article>
</template>
