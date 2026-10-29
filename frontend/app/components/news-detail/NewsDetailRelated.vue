<script setup lang="ts">
import type { PublicNewsDetailRelated } from '#shared/contracts/public-news-detail'

defineProps<{ items: PublicNewsDetailRelated[] }>()
const links = useMigrationLinks()

function formatDate(value: string): string {
  const date = new Date(`${value}T00:00:00Z`)
  return Number.isNaN(date.valueOf()) ? value : new Intl.DateTimeFormat('vi-VN', { dateStyle: 'short', timeZone: 'UTC' }).format(date)
}
</script>

<template>
  <aside v-if="items.length" class="pt-8" aria-labelledby="related-news-title">
    <h2 id="related-news-title" class="mb-5 text-2xl font-black text-navy-800">Tin liên quan</h2>
    <ul class="m-0 grid list-none grid-cols-1 gap-6 p-0 tb:grid-cols-2 nav:grid-cols-3">
      <li v-for="item in items" :key="item.id" class="min-w-0">
        <a class="group block h-full text-navy-800 no-underline" :href="links.news(item.slug)">
          <img v-if="item.image" :src="item.image" :alt="item.title" class="mb-3 block aspect-video w-full object-cover" loading="lazy" decoding="async">
          <div v-else class="mb-3 aspect-video bg-navy-50" aria-hidden="true" />
          <p v-if="item.date" class="mb-2 text-sm text-slate-500">{{ formatDate(item.date) }}</p>
          <h3 class="mb-2 text-lg font-bold leading-snug group-hover:text-gold-700">{{ item.title }}</h3>
          <p v-if="item.description" class="mb-0 line-clamp-3 leading-relaxed text-slate-600">{{ item.description }}</p>
        </a>
      </li>
    </ul>
  </aside>
</template>
