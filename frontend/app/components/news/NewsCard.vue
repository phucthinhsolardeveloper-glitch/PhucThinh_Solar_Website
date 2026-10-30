<script setup lang="ts">
import type { PublicNewsArticle } from '#shared/contracts/public-news'

/**
 * `variant` thay cho cơ chế cũ (component cha gắn class `news-card--lead` /
 * `news-card--secondary` lên gốc). Tailwind cần biết biến thể NGAY TRONG
 * component để đổi bố cục (ngang/dọc), không chỉ đổi màu — class rơi từ cha
 * xuống không đủ để bật layout khác nhau.
 */
withDefaults(defineProps<{
  article: PublicNewsArticle
  readLabel: string
  readMoreLabel: string
  variant?: 'grid' | 'lead' | 'secondary'
}>(), { variant: 'grid' })
const links = useMigrationLinks()
function cardImage(src: string): string {
  return /^\/upload\/news\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp|avif)$/i.test(src)
    ? `/thumbs/640x360x1${src}`
    : src
}
</script>

<template>
  <!--
    Bố cục đo từ ảnh production (/tin-tuc/, 1920px):
    - lead (ô lớn bên trái, chiếm 2 hàng): ảnh 16:9, heading 24px
    - secondary (2 ô bên phải): media chiếm 38% bề ngang, nằm ngang với phần chữ
    - grid (lưới 3 cột bên dưới): dọc như lead, heading 20px
  -->
  <!-- Bỏ grid cha + subgrid: không cần, <a> tự grid trực tiếp là đủ và dễ đọc hơn. -->
  <article class="min-w-0">
    <a
      :href="links.news(article.slug)"
      class="group flex h-full flex-col text-navy-800 no-underline transition-all duration-300 hover:-translate-y-1"
      :class="variant === 'secondary'
        ? 'grid grid-cols-[minmax(0,38%)_minmax(0,1fr)] gap-4 max-ph:flex max-ph:flex-col'
        : ''"
    >
      <div
        class="relative overflow-hidden bg-slate-50"
        :class="variant === 'secondary'
          ? 'aspect-video h-full min-h-[150px] rounded-chip max-ph:aspect-video max-ph:h-auto max-ph:min-h-0'
          : (variant === 'grid' ? 'aspect-video rounded-chip' : 'aspect-video rounded-chip')"
      >
        <img
          v-if="article.image"
          :src="cardImage(article.image)"
          :alt="article.title"
          class="block h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
          loading="lazy"
          decoding="async"
          width="640"
          height="360"
          sizes="(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw"
        >
        <span v-else class="grid h-full place-items-center text-[42px] text-gold-700" aria-hidden="true">✦</span>
        <span
          v-if="article.category"
          class="absolute left-3 top-3 rounded-chip bg-navy-800 px-2.5 py-1.5 text-[11px] font-bold text-white"
        >
          {{ article.category.name }}
        </span>
      </div>

      <div class="flex-1 py-[15px] pb-5" :class="variant === 'secondary' ? 'pt-2' : (variant === 'grid' ? 'p-4' : '')">
        <p v-if="article.date || article.readingMinutes" class="mb-2 flex flex-wrap gap-3.5 text-xs text-slate-500">
          <time v-if="article.date" :datetime="article.date">{{ article.date.split('-').reverse().join('/') }}</time>
          <span v-if="article.readingMinutes">{{ article.readingMinutes }} {{ readLabel }}</span>
        </p>
        <h2
          class="mb-2 line-clamp-2 leading-[1.35] text-navy-800 transition-colors group-hover:text-gold-700"
          :class="variant === 'lead' ? 'text-2xl' : 'text-xl'"
        >
          {{ article.title }}
        </h2>
        <p v-if="article.description" class="m-0 line-clamp-3 pr-0 leading-relaxed text-slate-500 nav:pr-[46px]">
          {{ article.description }}
        </p>
        <span
          class="inline-flex items-center gap-1.5 text-[13px] font-bold text-gold-700"
          :class="variant === 'secondary' ? 'mt-2.5' : 'mt-[13px]'"
        >
          <span>{{ readMoreLabel }}</span>
          <span class="inline-block transition-transform duration-300 group-hover:translate-x-1.5" aria-hidden="true">→</span>
        </span>
      </div>
    </a>
  </article>
</template>
