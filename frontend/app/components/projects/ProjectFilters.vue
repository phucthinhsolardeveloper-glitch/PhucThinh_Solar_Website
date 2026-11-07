<script setup lang="ts">
import type { PublicProjectCategory } from '#shared/contracts/public-projects'

defineProps<{ categories: PublicProjectCategory[]; selected: PublicProjectCategory | null; allLabel: string; filtersLabel: string }>()
const filtersPath = '/du-an-da-thuc-hien/'
const railRef = ref<HTMLElement | null>(null)
useActiveHorizontalRail(railRef)
useSlidingPill(railRef, 'projects')

function categoryHref(category: PublicProjectCategory): string {
  return category.url
}
</script>

<template>
  <nav ref="railRef" class="flex gap-2 overflow-x-auto py-0.5 pb-6 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" :aria-label="filtersLabel">
    <NuxtLink
      class="public-filter-chip flex min-h-11 flex-none items-center gap-2 whitespace-nowrap px-[15px] text-[15px] font-bold no-underline"
      :class="!selected ? 'text-navy-950' : 'text-navy-800'"
      :to="filtersPath"
      :aria-current="!selected ? 'page' : undefined"
    >
      {{ allLabel }}
    </NuxtLink>
    <NuxtLink
      v-for="category in categories"
      :key="category.id"
      class="public-filter-chip flex min-h-11 flex-none items-center gap-2 whitespace-nowrap px-[15px] text-[15px] font-bold no-underline"
      :class="selected?.id === category.id ? 'text-navy-950' : 'text-navy-800'"
      :to="categoryHref(category)"
      :aria-current="selected?.id === category.id ? 'page' : undefined"
    >
      <img v-if="category.image" :src="category.image" alt="" class="h-[22px] w-[22px] object-contain" width="22" height="22">
      {{ category.name }}
    </NuxtLink>
  </nav>
</template>
