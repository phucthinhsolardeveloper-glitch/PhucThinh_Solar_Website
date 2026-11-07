<script setup lang="ts">
import type { PublicNewsCategory } from '#shared/contracts/public-news'

const props = defineProps<{ categories: PublicNewsCategory[]; selected: PublicNewsCategory | null; allLabel: string; filtersLabel: string; query?: string }>()
const queryString = computed(() => props.query ? `?q=${encodeURIComponent(props.query)}` : '')
const filtersPath = '/tin-tuc/'
const railRef = ref<HTMLElement | null>(null)
useActiveHorizontalRail(railRef)
useSlidingPill(railRef, 'news')

function categoryHref(category: PublicNewsCategory): string {
  return `${category.canonicalPath}${queryString.value}`
}
</script>

<template>
  <nav class="flex w-full max-w-full min-w-0 gap-[7px] overflow-x-auto overscroll-x-contain px-0.5 pb-2 pt-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" ref="railRef" :aria-label="filtersLabel">
    <NuxtLink
      class="public-filter-chip flex min-h-11 flex-none items-center gap-2 whitespace-nowrap px-[15px] text-[15px] font-bold no-underline"
      :class="!selected ? 'text-navy-950' : 'text-navy-800'"
      :to="`${filtersPath}${queryString}`"
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
