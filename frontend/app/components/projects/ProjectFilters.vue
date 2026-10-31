<script setup lang="ts">
import type { PublicProjectCategory } from '#shared/contracts/public-projects'

defineProps<{ categories: PublicProjectCategory[]; selected: PublicProjectCategory | null; allLabel: string; filtersLabel: string }>()
// Preview filters use the DTO query endpoint; public filters use the PHP-owned category URL.
const { active: isMigrationPreview } = useMigrationLinks()
const filtersPath = computed(() => isMigrationPreview.value ? '/migration-projects-preview/' : '/du-an-da-thuc-hien/')

function categoryHref(category: PublicProjectCategory): string {
  return isMigrationPreview.value
    ? `${filtersPath.value}?category=${encodeURIComponent(category.slug)}`
    : category.url
}
</script>

<template>
  <nav class="flex gap-2 overflow-x-auto py-0.5 pb-6 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" :aria-label="filtersLabel">
    <NuxtLink
      class="public-filter-chip flex min-h-11 flex-none items-center gap-[7px] whitespace-nowrap px-[13px] no-underline"
      :class="!selected ? 'bg-navy-800 text-white' : 'text-navy-800'"
      :to="filtersPath"
      :aria-current="!selected ? 'page' : undefined"
    >
      {{ allLabel }}
    </NuxtLink>
    <NuxtLink
      v-for="category in categories"
      :key="category.id"
      class="public-filter-chip flex min-h-11 flex-none items-center gap-[7px] whitespace-nowrap px-[13px] no-underline"
      :class="selected?.id === category.id ? 'bg-navy-800 text-white' : 'text-navy-800'"
      :to="categoryHref(category)"
      :aria-current="selected?.id === category.id ? 'page' : undefined"
    >
      <img v-if="category.image" :src="category.image" alt="" class="h-6 w-6 object-contain" width="24" height="24">
      {{ category.name }}
    </NuxtLink>
  </nav>
</template>
