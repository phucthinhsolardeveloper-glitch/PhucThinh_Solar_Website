<script setup lang="ts">
import type { PublicProjectCategory } from '#shared/contracts/public-projects'

defineProps<{ categories: PublicProjectCategory[]; selected: PublicProjectCategory | null; allLabel: string; filtersLabel: string }>()
// Keep category filtering inside Nuxt preview until the public Projects route exists.
const { active: isMigrationPreview } = useMigrationLinks()
const filtersPath = computed(() => isMigrationPreview.value ? '/migration-projects-preview/' : '/du-an-da-thuc-hien/')
</script>

<template>
  <!--
    Đo pixel trên ảnh production: chip đang chọn ("Tất cả") là NỀN NAVY ĐẶC +
    chữ trắng, không phải chỉ đổi màu viền như CSS preview cũ mô tả — cùng kiểu
    trạng thái active đã xác nhận ở trang Tin tức (SiteHeader, NewsFilters).
  -->
  <nav class="flex gap-2 overflow-x-auto py-0.5 pb-6 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" :aria-label="filtersLabel">
    <a
      class="flex min-h-[42px] flex-none items-center gap-[7px] whitespace-nowrap rounded-chip px-[13px] no-underline"
      :class="!selected ? 'bg-navy-800 text-white' : 'border border-gray-200 text-navy-800 hover:border-gold-700'"
      :href="filtersPath"
    >
      {{ allLabel }}
    </a>
    <a
      v-for="category in categories"
      :key="category.id"
      class="flex min-h-[42px] flex-none items-center gap-[7px] whitespace-nowrap rounded-chip px-[13px] no-underline"
      :class="selected?.id === category.id ? 'bg-navy-800 text-white' : 'border border-gray-200 text-navy-800 hover:border-gold-700'"
      :href="`${filtersPath}?category=${encodeURIComponent(category.slug)}`"
    >
      <img v-if="category.image" :src="category.image" alt="" class="h-6 w-6 object-contain" width="24" height="24">
      {{ category.name }}
    </a>
  </nav>
</template>
