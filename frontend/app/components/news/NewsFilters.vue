<script setup lang="ts">
import type { PublicNewsCategory } from '#shared/contracts/public-news'
const props = defineProps<{ categories: PublicNewsCategory[]; selected: PublicNewsCategory | null; allLabel: string; filtersLabel: string; query?: string }>()
const queryString = computed(() => props.query ? `?q=${encodeURIComponent(props.query)}` : '')
</script>
<template>
  <nav class="news-filters" :aria-label="filtersLabel"><a :class="{ 'is-active': !selected }" :href="`/migration-news-preview/${queryString}`">{{ allLabel }}</a><a v-for="category in categories" :key="category.id" :class="{ 'is-active': selected?.id === category.id }" :href="`/migration-news-preview/?${query ? `q=${encodeURIComponent(query)}&` : ''}category=${encodeURIComponent(category.slug)}`"><img v-if="category.image" :src="category.image" alt="" width="22" height="22">{{ category.name }}</a></nav>
</template>
