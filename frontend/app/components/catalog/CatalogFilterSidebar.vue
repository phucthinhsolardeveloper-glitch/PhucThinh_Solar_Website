<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'
import type { PublicCatalogPayload } from '#shared/contracts/public-catalog'
import type { PublicProductCategoryNode } from '#shared/contracts/public-site'
import CatalogCategoryBranch from './CatalogCategoryBranch.vue'

const props = withDefaults(defineProps<{
  categories: PublicProductCategoryNode[]
  selectedCategory: string
  filters: NonNullable<PublicCatalogPayload['filters']>
  selected: NonNullable<PublicCatalogPayload['selected']>
  keyword: string
  subcategoryTitle?: string
  filterLink: (overrides: { subcategory?: string; brand?: string; price?: string; need?: string; keyword?: string }) => RouteLocationRaw
  idPrefix?: string
  hideTitle?: boolean
  drawer?: boolean
}>(), {
  idPrefix: 'catalog-filter',
  hideTitle: false,
  drawer: false,
})

function toggledIds(list: number[], id: number): string {
  const next = list.includes(id) ? list.filter((item) => item !== id) : [...list, id]
  return next.join(',')
}
function toggledKeys(list: string[], key: string): string {
  const next = list.includes(key) ? list.filter((item) => item !== key) : [...list, key]
  return next.join(',')
}

const hasAnyOption = computed(() => props.filters.subcategories.length > 0 || props.filters.brands.length > 0 || props.filters.priceRanges.length > 0 || (props.filters.needs?.length ?? 0) > 0)
const hasCategories = computed(() => props.categories.length > 0)
const categoriesExpanded = ref(false)
const hasActiveFilter = computed(() => props.keyword !== '' || props.selected.subcategoryIds.length > 0 || props.selected.brandIds.length > 0 || props.selected.priceKeys.length > 0 || (props.selected.needKeys?.length ?? 0) > 0)
const categoryTitleId = computed(() => `${props.idPrefix}-category-title`)
const categoryListId = computed(() => `${props.idPrefix}-category-list`)
</script>

<template>
  <!-- Một panel duy nhất giữ danh mục, loại sản phẩm, thương hiệu và mức giá. -->
  <aside v-if="hasAnyOption || hasCategories" class="p-5" :class="drawer ? 'rounded-none bg-white' : 'rounded-chip bg-slate-100'" aria-label="Bộ lọc sản phẩm">
    <h2 v-if="!hideTitle" class="m-0 mb-4 text-xl font-black text-navy-950">BỘ LỌC</h2>

    <section v-if="hasCategories" :aria-labelledby="categoryTitleId">
      <h3 :id="categoryTitleId" class="m-0 mb-3 text-sm font-bold text-navy-950">Danh mục sản phẩm</h3>
      <button
        class="flex min-h-11 w-full items-center justify-between rounded-chip border border-gray-200 bg-white px-3 py-2 text-left font-extrabold text-navy-800 nav:hidden"
        type="button"
        :aria-expanded="categoriesExpanded"
        :aria-controls="categoryListId"
        @click="categoriesExpanded = !categoriesExpanded"
      >
        <span>{{ categoriesExpanded ? 'Ẩn danh mục' : (selectedCategory ? 'Mở danh sách danh mục' : 'Chọn danh mục') }}</span>
        <svg class="h-5 w-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5" /></svg>
      </button>
      <ul
        :id="categoryListId"
        class="m-0 list-none p-0 pt-3 nav:block nav:pt-0"
        :class="categoriesExpanded ? 'block' : 'hidden'"
      >
        <CatalogCategoryBranch
          v-for="category in categories"
          :key="category.id"
          :node="category"
          :selected="selectedCategory"
        />
      </ul>
    </section>

    <hr v-if="hasCategories && hasAnyOption" class="my-5 border-slate-200">

    <template v-if="filters.subcategories.length">
      <h3 class="m-0 mb-3 text-sm font-bold text-navy-950">{{ subcategoryTitle || 'Loại sản phẩm' }}</h3>
      <ul class="m-0 mb-4 list-none p-0">
        <li class="mb-2.5">
          <NuxtLink
            class="no-underline"
            :class="selected.subcategoryIds.length === 0 ? 'font-bold text-navy-950' : 'text-[#20344b] hover:text-navy-950'"
            :to="filterLink({ subcategory: '' })"
            :aria-current="selected.subcategoryIds.length === 0 ? 'true' : undefined"
          >
            Tất cả
          </NuxtLink>
        </li>
        <li v-for="item in filters.subcategories" :key="item.id" class="mb-2.5">
          <NuxtLink
            class="flex items-center gap-2.5 text-[#20344b] no-underline hover:text-navy-950"
            :to="filterLink({ subcategory: toggledIds(selected.subcategoryIds, item.id) })"
            :aria-current="selected.subcategoryIds.includes(item.id) ? 'true' : undefined"
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

    <template v-if="filters.needs?.length">
      <h3 class="m-0 mb-3 text-sm font-bold text-navy-950">Nhu cầu sử dụng</h3>
      <ul class="m-0 mb-4 list-none p-0">
        <li v-for="item in filters.needs" :key="item.key" class="mb-2.5">
          <NuxtLink
            class="flex items-center gap-2.5 text-[#20344b] no-underline hover:text-navy-950"
            :to="filterLink({ need: toggledKeys(selected.needKeys ?? [], item.key) })"
            :aria-current="(selected.needKeys ?? []).includes(item.key) ? 'true' : undefined"
          >
            <span
              class="grid h-[18px] w-[18px] flex-none place-items-center rounded-[3px] border-[1.5px] border-navy-950"
              :class="(selected.needKeys ?? []).includes(item.key) ? 'bg-navy-950' : 'bg-white'"
              aria-hidden="true"
            >
              <svg v-if="(selected.needKeys ?? []).includes(item.key)" class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7" /></svg>
            </span>
            {{ item.label }}
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
            :aria-current="selected.brandIds.includes(brand.id) ? 'true' : undefined"
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
            :aria-current="selected.priceKeys.includes(range.key) ? 'true' : undefined"
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
      :to="filterLink({ subcategory: '', brand: '', price: '', need: '', keyword: '' })"
    >
      <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5" /></svg>
      Xóa bộ lọc
    </NuxtLink>
  </aside>
</template>
