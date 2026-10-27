<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'
import type { PublicCatalogPayload } from '#shared/contracts/public-catalog'

const props = defineProps<{
  filters: NonNullable<PublicCatalogPayload['filters']>
  selected: NonNullable<PublicCatalogPayload['selected']>
  filterLink: (overrides: { subcategory?: string; brand?: string; price?: string }) => RouteLocationRaw
}>()

function toggledIds(list: number[], id: number): string {
  const next = list.includes(id) ? list.filter((item) => item !== id) : [...list, id]
  return next.join(',')
}
function toggledKeys(list: string[], key: string): string {
  const next = list.includes(key) ? list.filter((item) => item !== key) : [...list, key]
  return next.join(',')
}

const hasAnyOption = computed(() => props.filters.subcategories.length > 0 || props.filters.brands.length > 0 || props.filters.priceRanges.length > 0)
const hasActiveFilter = computed(() => props.selected.subcategoryIds.length > 0 || props.selected.brandIds.length > 0 || props.selected.priceKeys.length > 0)
</script>

<template>
  <!--
    Đo trên ảnh production (/danh-muc-san-pham/tam-pin-nang-luong-mat-troi/,
    1920px): nền panel #f2f5fa (khớp slate-100 #f1f5f9 trong sai số đo), tiêu
    đề/khung checkbox #071b3e (khớp navy-950 #061537), chữ nhãn/brand/giá
    #20344b (không khớp token thương hiệu nào có sẵn — dùng màu đo trực tiếp).
    Trang catalog còn có thêm hai "tab" rộng Tất cả/Kẹp tấm pin nền vàng phía
    trên lưới sản phẩm, trùng chức năng với mục "Loại sản phẩm" ở đây — CHƯA
    dựng lại tab đó (trùng lặp chức năng, ưu tiên lọc hoạt động đúng trước).
  -->
  <aside v-if="hasAnyOption" class="rounded-chip bg-slate-100 p-5" aria-label="Bộ lọc sản phẩm">
    <h2 class="m-0 mb-4 text-xl font-black text-navy-950">BỘ LỌC</h2>

    <template v-if="filters.subcategories.length">
      <h3 class="m-0 mb-3 text-sm font-bold text-navy-950">Loại sản phẩm</h3>
      <ul class="m-0 mb-4 list-none p-0">
        <li class="mb-2.5">
          <NuxtLink
            class="no-underline"
            :class="selected.subcategoryIds.length === 0 ? 'font-bold text-navy-950' : 'text-[#20344b] hover:text-navy-950'"
            :to="filterLink({ subcategory: '' })"
          >
            Tất cả
          </NuxtLink>
        </li>
        <li v-for="item in filters.subcategories" :key="item.id" class="mb-2.5">
          <NuxtLink
            class="flex items-center gap-2.5 text-[#20344b] no-underline hover:text-navy-950"
            :to="filterLink({ subcategory: toggledIds(selected.subcategoryIds, item.id) })"
          >
            <span
              class="grid h-[18px] w-[18px] flex-none place-items-center rounded-[3px] border-[1.5px] border-navy-950"
              :class="selected.subcategoryIds.includes(item.id) ? 'bg-navy-950' : 'bg-white'"
              aria-hidden="true"
            >
              <svg v-if="selected.subcategoryIds.includes(item.id)" class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7" /></svg>
            </span>
            {{ item.name }}
          </NuxtLink>
        </li>
      </ul>
      <hr class="mb-4 border-slate-200">
    </template>

    <template v-if="filters.brands.length">
      <h3 class="m-0 mb-3 text-sm font-bold text-navy-950">Thương hiệu</h3>
      <ul class="m-0 mb-4 list-none p-0">
        <li v-for="brand in filters.brands" :key="brand.id" class="mb-2.5">
          <NuxtLink
            class="flex items-center gap-2.5 text-[#20344b] no-underline hover:text-navy-950"
            :to="filterLink({ brand: toggledIds(selected.brandIds, brand.id) })"
          >
            <span
              class="grid h-[18px] w-[18px] flex-none place-items-center rounded-[3px] border-[1.5px] border-navy-950"
              :class="selected.brandIds.includes(brand.id) ? 'bg-navy-950' : 'bg-white'"
              aria-hidden="true"
            >
              <svg v-if="selected.brandIds.includes(brand.id)" class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7" /></svg>
            </span>
            {{ brand.name }}
          </NuxtLink>
        </li>
      </ul>
      <hr class="mb-4 border-slate-200">
    </template>

    <template v-if="filters.priceRanges.length">
      <h3 class="m-0 mb-3 text-sm font-bold text-navy-950">Mức giá</h3>
      <ul class="m-0 list-none p-0" :class="hasActiveFilter ? 'mb-4' : ''">
        <li v-for="range in filters.priceRanges" :key="range.key" class="mb-2.5">
          <NuxtLink
            class="flex items-center gap-2.5 text-[#20344b] no-underline hover:text-navy-950"
            :to="filterLink({ price: toggledKeys(selected.priceKeys, range.key) })"
          >
            <span
              class="grid h-[18px] w-[18px] flex-none place-items-center rounded-[3px] border-[1.5px] border-navy-950"
              :class="selected.priceKeys.includes(range.key) ? 'bg-navy-950' : 'bg-white'"
              aria-hidden="true"
            >
              <svg v-if="selected.priceKeys.includes(range.key)" class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7" /></svg>
            </span>
            {{ range.label }}
          </NuxtLink>
        </li>
      </ul>
    </template>

    <NuxtLink
      v-if="hasActiveFilter"
      class="flex items-center gap-1.5 text-sm text-[#20344b] underline underline-offset-2 hover:text-navy-950"
      :to="filterLink({ subcategory: '', brand: '', price: '' })"
    >
      <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5" /></svg>
      Xóa bộ lọc
    </NuxtLink>
  </aside>
</template>
