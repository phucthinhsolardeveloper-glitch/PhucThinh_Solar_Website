<script setup lang="ts">
import type { PublicNewsDetailPayload } from '#shared/contracts/public-news-detail'
import EditorialRichText from '~/components/editorial/EditorialRichText.vue'
import EditorialShare from '~/components/editorial/EditorialShare.vue'
import { buildEditorialToc } from '#shared/editorial-toc'

const props = defineProps<{ article: PublicNewsDetailPayload['article'] }>()
const editorial = computed(() => buildEditorialToc(props.article.content))

function formatDate(value: string): string {
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value)
  if (!match) return value
  const date = new Date(`${value}T00:00:00.000Z`)
  if (Number.isNaN(date.valueOf()) || date.toISOString().slice(0, 10) !== value) return value
  return new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'UTC' }).format(date)
}
</script>

<template>
  <!--
    Không có ảnh production riêng cho trang chi tiết tin tức trong bộ 13 ảnh
    đã chụp — dựng theo cấu trúc CSS gốc (news-detail-preview.css) và nhất
    quán với PDP/dự án/giải pháp chi tiết đã đo (container 1766px, heading
    navy-800, .cms-content cho nội dung dài).
  -->
  <article class="mx-auto w-full max-w-[1080px]">
    <h1 class="mb-4 text-[clamp(28px,3.6vw,42px)] font-black leading-tight text-navy-800">{{ article.title }}</h1>
    <p class="mb-4 text-slate-500"><time v-if="article.date" :datetime="article.date">{{ formatDate(article.date) }}</time><span v-if="article.date" aria-hidden="true"> · </span>{{ article.author.name }}</p>
    <img v-if="article.cover && article.showCover" :src="article.cover" :alt="article.title" class="my-6 block aspect-video w-full rounded-chip object-cover">

    <nav v-if="article.content.html && editorial.toc.length" class="mb-6 rounded-btn border border-slate-200/90 border-l-4 border-l-gold-500 bg-slate-50/70 p-5 shadow-xs" aria-label="Mục lục bài viết">
      <h2 class="mb-3 flex items-center gap-2 text-sm font-extrabold uppercase tracking-wider text-navy-950">
        <svg class="h-4 w-4 text-gold-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />
        </svg>
        <span>Nội dung bài viết</span>
      </h2>
      <ol class="m-0 list-none space-y-1 p-0">
        <li v-for="item in editorial.toc" :key="item.id" :class="item.level === 3 ? 'pl-4' : ''">
          <a class="inline-flex min-h-11 min-w-11 items-center px-2 text-sm font-semibold text-navy-800 underline-offset-2 hover:text-gold-700 hover:underline" :href="`#${item.id}`">{{ item.label }}</a>
        </li>
      </ol>
    </nav>

    <!-- Sanitized rich text gets only legacy-compatible heading IDs added for TOC anchors. -->
    <EditorialRichText v-if="article.content.html" :content="editorial.content" />
    <p v-else class="my-8 text-slate-600" role="status">Nội dung đang cập nhật</p>

    <div v-if="editorial.content.html && article.quote?.html" class="my-8">
      <blockquote class="m-0 border-l-[3px] border-gold-500 pl-4 text-slate-600">
        <EditorialRichText :content="article.quote" />
      </blockquote>
    </div>

    <EditorialShare v-if="article.content.html" :title="article.title" :canonical-path="article.canonical" />

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
