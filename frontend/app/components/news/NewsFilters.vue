<script setup lang="ts">
import type { PublicNewsCategory } from '#shared/contracts/public-news'

const props = defineProps<{ categories: PublicNewsCategory[]; selected: PublicNewsCategory | null; allLabel: string; filtersLabel: string; query?: string }>()
const queryString = computed(() => props.query ? `?q=${encodeURIComponent(props.query)}` : '')
</script>

<template>
  <!--
    Trạng thái đang chọn là NỀN NAVY ĐẶC + chữ trắng, không phải gold-light như
    CSS preview cũ mô tả — đo pixel trên ảnh production xác nhận chip "Tất cả"
    đang chọn màu navy-800, các chip còn lại nền slate-50 + chữ navy.
  -->
  <nav class="flex min-w-0 gap-[7px] overflow-x-auto px-0.5 pb-2 pt-1" :aria-label="filtersLabel">
    <a
      class="flex min-h-[42px] flex-none items-center gap-2 whitespace-nowrap rounded-chip px-[15px] text-[13px] font-bold no-underline"
      :class="!selected ? 'bg-navy-800 text-white' : 'bg-slate-50 text-navy-800 hover:bg-gray-200'"
      :href="`/migration-news-preview/${queryString}`"
    >
      {{ allLabel }}
    </a>
    <a
      v-for="category in categories"
      :key="category.id"
      class="flex min-h-[42px] flex-none items-center gap-2 whitespace-nowrap rounded-chip px-[15px] text-[13px] font-bold no-underline"
      :class="selected?.id === category.id ? 'bg-navy-800 text-white' : 'bg-slate-50 text-navy-800 hover:bg-gray-200'"
      :href="`/migration-news-preview/?${query ? `q=${encodeURIComponent(query)}&` : ''}category=${encodeURIComponent(category.slug)}`"
    >
      <img v-if="category.image" :src="category.image" alt="" class="h-[22px] w-[22px] object-contain" width="22" height="22">
      {{ category.name }}
    </a>
  </nav>
</template>
