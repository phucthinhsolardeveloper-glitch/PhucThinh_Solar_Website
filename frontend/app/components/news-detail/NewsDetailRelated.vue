<script setup lang="ts">
import type { PublicNewsDetailRelated } from '#shared/contracts/public-news-detail'

defineProps<{ items: PublicNewsDetailRelated[] }>()
const links = useMigrationLinks()
const track = ref<HTMLUListElement | null>(null)

function formatDate(value: string): string {
  const date = new Date(`${value}T00:00:00Z`)
  return Number.isNaN(date.valueOf()) ? value : new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'UTC' }).format(date)
}

function scrollRelated(direction: -1 | 1): void {
  const element = track.value
  if (!element) return
  element.scrollBy({ left: direction * element.clientWidth, behavior: 'smooth' })
}
</script>

<template>
  <aside v-if="items.length" class="pt-8" aria-labelledby="related-news-title">
    <div class="mb-5 flex items-center justify-between gap-4">
      <h2 id="related-news-title" class="m-0 text-2xl font-black text-navy-800">Tin liên quan</h2>
      <div v-if="items.length > 1" role="group" class="flex shrink-0 gap-2" aria-label="Điều hướng tin liên quan">
        <button type="button" class="grid size-9 place-items-center rounded-full text-navy-800 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-600" aria-label="Tin trước" @click="scrollRelated(-1)">‹</button>
        <button type="button" class="grid size-9 place-items-center rounded-full text-navy-800 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-600" aria-label="Tin tiếp theo" @click="scrollRelated(1)">›</button>
      </div>
    </div>
    <ul ref="track" class="related-track m-0 flex list-none snap-x snap-mandatory gap-6 overflow-x-auto scroll-smooth p-0">
      <li v-for="item in items" :key="item.id" class="min-w-0 w-full shrink-0 snap-start tb:w-[calc((100%_-_1.5rem)/2)] nav:w-[calc((100%_-_4.5rem)/4)]">
        <a class="group block h-full text-navy-800 no-underline" :href="links.news(item.slug)">
          <img v-if="item.image" :src="item.image" :alt="item.title" class="mb-3 block aspect-video w-full object-cover" loading="lazy" decoding="async" width="640" height="360" sizes="(min-width: 1100px) 25vw, (min-width: 768px) 50vw, 100vw">
          <div v-else class="mb-3 aspect-video bg-navy-50" aria-hidden="true" />
          <p v-if="item.date || item.author" class="mb-2 flex flex-wrap items-center gap-3 text-xs text-slate-500">
            <time v-if="item.date" :datetime="item.date">{{ formatDate(item.date) }}</time>
            <span v-if="item.author">{{ item.author }}</span>
          </p>
          <h3 class="mb-2 line-clamp-2 text-[15px] font-bold leading-snug group-hover:text-gold-700">{{ item.title }}</h3>
          <p v-if="item.description" class="mb-0 line-clamp-2 text-sm leading-relaxed text-slate-600">{{ item.description }}</p>
        </a>
      </li>
    </ul>
  </aside>
</template>

<style scoped>
.related-track { scrollbar-width: none; }
.related-track::-webkit-scrollbar { display: none; }
</style>
