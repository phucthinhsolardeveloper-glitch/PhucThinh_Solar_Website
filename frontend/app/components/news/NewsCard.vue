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
const phpResizableNewsImage = /^\/upload\/news\/[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|webp)$/i

function cardImage(src: string, width: 600 | 900, height: 338 | 506): string {
  return phpResizableNewsImage.test(src) ? phpThumb(src, width, height) : src
}

function cardImageSrcSet(src: string): string | undefined {
  if (!phpResizableNewsImage.test(src)) return undefined
  return `${cardImage(src, 600, 338)} 600w, ${cardImage(src, 900, 506)} 900w`
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
      class="group flex h-full flex-col text-navy-800 no-underline"
      :class="[variant === 'secondary'
        ? 'grid grid-cols-[minmax(0,38%)_minmax(0,1fr)] gap-4'
        : '', variant === 'lead' ? '' : 'news-row']"
    >
      <div
        class="news-thumb relative overflow-hidden bg-slate-50"
        :class="variant === 'secondary'
          ? 'aspect-video h-auto min-h-0 w-full min-w-0 self-start rounded-chip max-ph:aspect-video max-ph:h-auto max-ph:min-h-0'
          : 'aspect-video rounded-chip'"
      >
        <img
          v-if="article.image"
          :src="cardImage(article.image, variant === 'lead' ? 900 : 600, variant === 'lead' ? 506 : 338)"
          :srcset="cardImageSrcSet(article.image)"
          :sizes="variant === 'lead' ? '(min-width: 1024px) 50vw, 100vw' : variant === 'secondary' ? '(min-width: 1024px) 19vw, 100vw' : '(min-width: 1024px) 33vw, (min-width: 768px) 50vw, 100vw'"
          :alt="sentenceCase(article.title)"
          class="block h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-[1.025] motion-reduce:transition-none motion-reduce:transform-none"
          :loading="variant === 'lead' ? 'eager' : 'lazy'"
          :fetchpriority="variant === 'lead' ? 'high' : 'low'"
          decoding="async"
          :width="variant === 'lead' ? 900 : 600"
          :height="variant === 'lead' ? 506 : 338"
        >
        <span v-else class="grid h-full place-items-center text-[42px] text-gold-700" aria-hidden="true">✦</span>
        <span
          v-if="article.category"
          class="news-badge absolute left-3 top-3 rounded-full bg-navy-950/85 px-3 py-1.5 text-xs font-bold text-white backdrop-blur-xs"
        >
          {{ article.category.name }}
        </span>
      </div>

      <div class="news-body flex-1 py-[15px] pb-5" :class="variant === 'secondary' ? 'pt-2' : (variant === 'grid' ? 'p-4' : '')">
        <p v-if="article.date || article.readingMinutes" class="mb-2 flex flex-wrap items-center gap-3.5 text-xs text-slate-500">
          <time v-if="article.date" :datetime="article.date" class="inline-flex items-center gap-1.5">
            <svg class="h-3.5 w-3.5 text-gold-600 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
            <span>{{ article.date.split('-').reverse().join('/') }}</span>
          </time>
          <span v-if="article.readingMinutes" class="inline-flex items-center gap-1.5">
            <svg class="h-3.5 w-3.5 text-gold-600 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>{{ article.readingMinutes }} {{ readLabel }}</span>
          </span>
        </p>
        <h2
          class="news-title mb-2 line-clamp-2 font-extrabold leading-[1.3] tracking-[-0.01em] text-navy-950 transition-colors group-hover:text-gold-700 group-focus-visible:text-gold-700"
          :class="variant === 'lead' ? 'text-2xl' : 'text-xl'"
        >
          {{ sentenceCase(article.title) }}
        </h2>
        <p v-if="article.description" class="news-excerpt m-0 line-clamp-3 pr-0 leading-relaxed text-slate-500 nav:pr-[46px]">
          {{ article.description }}
        </p>
        <span
          class="news-more inline-flex items-center gap-1.5 text-[13px] font-bold text-gold-700"
          :class="variant === 'secondary' ? 'mt-2.5' : 'mt-[13px]'"
        >
          <span>{{ readMoreLabel }}</span>
          <span class="inline-block transition-transform duration-300 group-hover:translate-x-1.5" aria-hidden="true">→</span>
        </span>
      </div>
    </a>
  </article>
</template>

<style scoped>
@media (max-width: 820px) {
  .news-row { display: grid !important; grid-template-columns: 116px minmax(0, 1fr); gap: 12px; align-items: start; }
  .news-row .news-thumb { aspect-ratio: 1 !important; width: 100%; border-radius: var(--radius-md); }
  .news-row .news-body { padding: 0 !important; }
  .news-row .news-title { margin-bottom: 0; font-size: 15px !important; line-height: 1.35; -webkit-line-clamp: 3; }
  .news-row .news-more, .news-row .news-badge { display: none !important; }
  .news-row .news-excerpt { margin-top: 4px; font-size: 13.5px; line-height: 1.45; -webkit-line-clamp: 2; }
}
</style>
