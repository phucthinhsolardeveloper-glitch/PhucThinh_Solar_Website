<script setup lang="ts">
import type { PublicProjectCategory } from '#shared/contracts/public-projects'
defineProps<{ categories: PublicProjectCategory[]; selected: PublicProjectCategory | null; allLabel: string; filtersLabel: string }>()
</script>
<template>
  <nav class="projects-filters" :aria-label="filtersLabel">
    <a :class="{ 'is-active': !selected }" href="/migration-projects-preview/">{{ allLabel }}</a>
    <a v-for="category in categories" :key="category.id" :class="{ 'is-active': selected?.id === category.id }" :href="`/migration-projects-preview/?category=${encodeURIComponent(category.slug)}`">
      <img v-if="category.image" :src="category.image" alt="" width="24" height="24">{{ category.name }}
    </a>
  </nav>
</template>
