<script setup lang="ts">
import type { PublicNewsCategory } from '#shared/contracts/public-news'

const props = defineProps<{ categories: PublicNewsCategory[]; selected: PublicNewsCategory | null; allLabel: string; filtersLabel: string; query?: string }>()
const queryString = computed(() => props.query ? `?q=${encodeURIComponent(props.query)}` : '')
// Preview filters must stay in Nuxt even before the canonical public News route is enabled.
const { active: isMigrationPreview } = useMigrationLinks()
const filtersPath = computed(() => isMigrationPreview.value ? '/migration-news-preview/' : '/tin-tuc/')

function categoryHref(category: PublicNewsCategory): string {
  if (!isMigrationPreview.value) return `${category.canonicalPath}${queryString.value}`

  const params = new URLSearchParams()
  if (props.query) params.set('q', props.query)
  params.set('category', category.slug)
  return `${filtersPath.value}?${params.toString()}`
}
</script>

<template>
  <!--
    Trạng thái đang chọn là NỀN NAVY ĐẶC + chữ trắng, không phải gold-light như
    CSS preview cũ mô tả — đo pixel trên ảnh production xác nhận chip "Tất cả"
    đang chọn màu navy-800, các chip còn lại nền slate-50 + chữ navy.
  -->
  <nav class="flex w-full max-w-full min-w-0 gap-[7px] overflow-x-auto overscroll-x-contain px-0.5 pb-2 pt-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" :aria-label="filtersLabel">
    <a
      class="flex min-h-11 flex-none items-center gap-2 whitespace-nowrap rounded-chip px-[15px] text-[15px] font-bold no-underline"
      :class="!selected ? 'bg-navy-800 text-white' : 'bg-slate-50 text-navy-800 hover:bg-gray-200'"
      :href="`${filtersPath}${queryString}`"
    >
      {{ allLabel }}
    </a>
    <a
      v-for="category in categories"
      :key="category.id"
      class="flex min-h-11 flex-none items-center gap-2 whitespace-nowrap rounded-chip px-[15px] text-[15px] font-bold no-underline"
      :class="selected?.id === category.id ? 'bg-navy-800 text-white' : 'bg-slate-50 text-navy-800 hover:bg-gray-200'"
      :href="categoryHref(category)"
    >
      <img v-if="category.image" :src="category.image" alt="" class="h-[22px] w-[22px] object-contain" width="22" height="22">
      {{ category.name }}
    </a>
  </nav>
</template>
