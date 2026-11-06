<script setup lang="ts">
import { AnimatePresence, motion, useReducedMotion } from 'motion-v'
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

const reducedMotion = useReducedMotion()
const hasAnyOption = computed(() => props.filters.subcategories.length > 0 || props.filters.brands.length > 0 || props.filters.priceRanges.length > 0 || (props.filters.needs?.length ?? 0) > 0)
const hasCategories = computed(() => props.categories.length > 0)
const categoriesExpanded = ref(false)
const categoryTitleId = computed(() => `${props.idPrefix}-category-title`)
const categoryListId = computed(() => `${props.idPrefix}-category-list`)

const activeFilters = computed(() => {
  const active: Array<{ key: string; label: string; to: RouteLocationRaw }> = []
  for (const id of props.selected.subcategoryIds) {
    const item = props.filters.subcategories.find((option) => option.id === id)
    if (item) active.push({ key: `subcategory-${id}`, label: item.name, to: props.filterLink({ subcategory: toggledIds(props.selected.subcategoryIds, id) }) })
  }
  for (const id of props.selected.brandIds) {
    const item = props.filters.brands.find((option) => option.id === id)
    if (item) active.push({ key: `brand-${id}`, label: item.name, to: props.filterLink({ brand: toggledIds(props.selected.brandIds, id) }) })
  }
  for (const key of props.selected.needKeys ?? []) {
    const item = props.filters.needs?.find((option) => option.key === key)
    if (item) active.push({ key: `need-${key}`, label: item.label, to: props.filterLink({ need: toggledKeys(props.selected.needKeys ?? [], key) }) })
  }
  for (const key of props.selected.priceKeys) {
    const item = props.filters.priceRanges.find((option) => option.key === key)
    if (item) active.push({ key: `price-${key}`, label: item.label, to: props.filterLink({ price: toggledKeys(props.selected.priceKeys, key) }) })
  }
  if (props.keyword) active.push({ key: 'keyword', label: `Từ khóa: ${props.keyword}`, to: props.filterLink({ keyword: '' }) })
  return active
})

const selectedCount = computed(() => activeFilters.value.length)
const hasActiveFilter = computed(() => selectedCount.value > 0)
const clearFiltersTo = computed(() => props.filterLink({ subcategory: '', brand: '', price: '', need: '', keyword: '' }))
</script>

<template>
  <aside
    v-if="hasAnyOption || hasCategories"
    class="min-w-0"
    :class="drawer ? 'bg-white px-4 pb-6 pt-4' : 'bg-slate-50 p-4'"
    aria-label="Bộ lọc sản phẩm"
  >
    <div v-if="!hideTitle" class="mb-5 flex items-center justify-between gap-3">
      <div class="flex min-w-0 items-center gap-2.5">
        <span class="grid size-9 flex-none place-items-center rounded-btn bg-gold-50 text-navy-800" aria-hidden="true">
          <UIcon name="i-lucide-sliders-horizontal" class="size-[18px]" />
        </span>
        <h2 class="m-0 text-lg font-extrabold text-navy-950">Bộ lọc</h2>
      </div>
      <UButton
        v-if="hasActiveFilter"
        :to="clearFiltersTo"
        color="neutral"
        variant="ghost"
        size="xs"
        class="min-h-9 flex-none rounded-btn px-2 text-xs font-bold text-navy-700 hover:bg-slate-100 hover:text-navy-950"
      >
        Xóa tất cả
      </UButton>
    </div>

    <section v-if="hasActiveFilter" class="mb-5 border-b border-slate-200 pb-5" aria-label="Bộ lọc đang áp dụng">
      <div class="mb-2.5 flex items-center gap-2">
        <h3 class="m-0 text-sm font-bold text-navy-950">Đang áp dụng</h3>
        <span class="inline-flex min-h-5 min-w-5 items-center justify-center rounded-chip bg-gold-400 px-1.5 text-[11px] font-extrabold leading-none text-navy-950">{{ selectedCount }}</span>
      </div>
      <AnimatePresence mode="popLayout">
        <motion.div
          v-for="filter in activeFilters"
          :key="filter.key"
          class="mb-2 inline-flex max-w-full align-top"
          :initial="reducedMotion ? false : { opacity: 0, scale: 0.96, y: 4 }"
          :animate="{ opacity: 1, scale: 1, y: 0 }"
          :exit="reducedMotion ? undefined : { opacity: 0, scale: 0.96, y: -3 }"
          :transition="{ duration: reducedMotion ? 0 : 0.14, ease: 'easeOut' }"
        >
          <NuxtLink
            :to="filter.to"
            class="group inline-flex min-h-9 max-w-full items-center gap-2 rounded-btn border border-gold-300 bg-gold-50 px-2.5 py-1 text-xs font-semibold leading-snug text-navy-900 no-underline transition-colors hover:border-gold-500 hover:bg-gold-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gold-700"
            :aria-label="`Bỏ bộ lọc ${filter.label}`"
          >
            <span class="min-w-0 truncate">{{ filter.label }}</span>
            <UIcon name="i-lucide-x" class="size-3.5 flex-none text-navy-700 transition-transform group-hover:rotate-90" aria-hidden="true" />
          </NuxtLink>
        </motion.div>
      </AnimatePresence>
    </section>

    <section v-if="hasCategories" class="mb-5 border-b border-slate-200 pb-5" :aria-labelledby="categoryTitleId">
      <div class="mb-2.5 flex items-center gap-2.5">
        <UIcon name="i-lucide-layout-grid" class="size-4 flex-none text-gold-700" aria-hidden="true" />
        <h3 :id="categoryTitleId" class="m-0 text-sm font-bold text-navy-950">Danh mục sản phẩm</h3>
      </div>
      <UButton
        class="public-neutral-button min-h-11 w-full justify-between rounded-btn px-3 text-left text-sm font-bold text-navy-800 nav:hidden"
        color="neutral"
        variant="outline"
        type="button"
        :aria-expanded="categoriesExpanded"
        :aria-controls="categoryListId"
        @click="categoriesExpanded = !categoriesExpanded"
      >
        <span>{{ categoriesExpanded ? 'Ẩn danh mục' : (selectedCategory ? 'Mở danh mục khác' : 'Chọn danh mục') }}</span>
        <UIcon name="i-lucide-chevron-down" class="size-4 transition-transform" :class="categoriesExpanded ? 'rotate-180' : ''" aria-hidden="true" />
      </UButton>
      <ul
        :id="categoryListId"
        class="m-0 list-none p-0 nav:block nav:pt-0"
        :class="categoriesExpanded ? 'block pt-3' : 'hidden'"
      >
        <CatalogCategoryBranch
          v-for="category in categories"
          :key="category.id"
          :node="category"
          :selected="selectedCategory"
        />
      </ul>
    </section>

    <section v-if="filters.subcategories.length" class="mb-5 border-b border-slate-200 pb-5" aria-labelledby="catalog-subcategory-filter-title">
      <div class="mb-2.5 flex items-center gap-2.5">
        <UIcon name="i-lucide-tags" class="size-4 flex-none text-gold-700" aria-hidden="true" />
        <h3 id="catalog-subcategory-filter-title" class="m-0 text-sm font-bold text-navy-950">{{ subcategoryTitle || 'Loại sản phẩm' }}</h3>
      </div>
      <ul class="m-0 list-none p-0">
        <li class="mb-1">
          <NuxtLink
            class="flex min-h-10 items-center justify-between gap-3 rounded-btn px-2.5 text-sm no-underline transition-colors hover:bg-slate-50"
            :class="selected.subcategoryIds.length === 0 ? 'font-bold text-navy-950' : 'text-slate-700'"
            :to="filterLink({ subcategory: '' })"
            :aria-current="selected.subcategoryIds.length === 0 ? 'true' : undefined"
          >
            <span>Tất cả</span>
            <UIcon v-if="selected.subcategoryIds.length === 0" name="i-lucide-check" class="size-4 flex-none text-gold-700" aria-hidden="true" />
          </NuxtLink>
        </li>
        <li v-for="item in filters.subcategories" :key="item.id" class="mb-1 last:mb-0">
          <NuxtLink
            class="group flex min-h-10 items-center gap-2.5 rounded-btn px-2.5 text-sm no-underline transition-colors hover:bg-gold-50 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-gold-700"
            :class="selected.subcategoryIds.includes(item.id) ? 'bg-gold-50 font-semibold text-navy-950' : 'text-slate-700'"
            :to="filterLink({ subcategory: toggledIds(selected.subcategoryIds, item.id) })"
            :aria-current="selected.subcategoryIds.includes(item.id) ? 'true' : undefined"
          >
            <span class="grid size-[18px] flex-none place-items-center rounded-chip border border-slate-400 bg-white transition-colors group-hover:border-gold-600" :class="selected.subcategoryIds.includes(item.id) ? 'border-transparent bg-gold-400' : ''" aria-hidden="true">
              <UIcon v-if="selected.subcategoryIds.includes(item.id)" name="i-lucide-check" class="size-3 text-navy-950" />
            </span>
            <span class="min-w-0 flex-1">{{ item.name }}</span>
          </NuxtLink>
        </li>
      </ul>
    </section>

    <section v-if="filters.needs?.length" class="mb-5 border-b border-slate-200 pb-5" aria-labelledby="catalog-needs-filter-title">
      <div class="mb-2.5 flex items-center gap-2.5">
        <UIcon name="i-lucide-lightbulb" class="size-4 flex-none text-gold-700" aria-hidden="true" />
        <h3 id="catalog-needs-filter-title" class="m-0 text-sm font-bold text-navy-950">Nhu cầu sử dụng</h3>
      </div>
      <ul class="m-0 list-none p-0">
        <li v-for="item in filters.needs" :key="item.key" class="mb-1 last:mb-0">
          <NuxtLink
            class="group flex min-h-10 items-center gap-2.5 rounded-btn px-2.5 text-sm no-underline transition-colors hover:bg-gold-50 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-gold-700"
            :class="(selected.needKeys ?? []).includes(item.key) ? 'bg-gold-50 font-semibold text-navy-950' : 'text-slate-700'"
            :to="filterLink({ need: toggledKeys(selected.needKeys ?? [], item.key) })"
            :aria-current="(selected.needKeys ?? []).includes(item.key) ? 'true' : undefined"
          >
            <span class="grid size-[18px] flex-none place-items-center rounded-chip border border-slate-400 bg-white transition-colors group-hover:border-gold-600" :class="(selected.needKeys ?? []).includes(item.key) ? 'border-transparent bg-gold-400' : ''" aria-hidden="true">
              <UIcon v-if="(selected.needKeys ?? []).includes(item.key)" name="i-lucide-check" class="size-3 text-navy-950" />
            </span>
            <span class="min-w-0 flex-1">{{ item.label }}</span>
          </NuxtLink>
        </li>
      </ul>
    </section>

    <section v-if="filters.brands.length" class="mb-5 border-b border-slate-200 pb-5" aria-labelledby="catalog-brand-filter-title">
      <div class="mb-2.5 flex items-center gap-2.5">
        <UIcon name="i-lucide-badge-check" class="size-4 flex-none text-gold-700" aria-hidden="true" />
        <h3 id="catalog-brand-filter-title" class="m-0 text-sm font-bold text-navy-950">Thương hiệu</h3>
      </div>
      <ul class="m-0 list-none p-0">
        <li v-for="brand in filters.brands" :key="brand.id" class="mb-1 last:mb-0">
          <NuxtLink
            class="group flex min-h-10 items-center gap-2.5 rounded-btn px-2.5 text-sm no-underline transition-colors hover:bg-gold-50 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-gold-700"
            :class="selected.brandIds.includes(brand.id) ? 'bg-gold-50 font-semibold text-navy-950' : 'text-slate-700'"
            :to="filterLink({ brand: toggledIds(selected.brandIds, brand.id) })"
            :aria-current="selected.brandIds.includes(brand.id) ? 'true' : undefined"
          >
            <span class="grid size-[18px] flex-none place-items-center rounded-chip border border-slate-400 bg-white transition-colors group-hover:border-gold-600" :class="selected.brandIds.includes(brand.id) ? 'border-transparent bg-gold-400' : ''" aria-hidden="true">
              <UIcon v-if="selected.brandIds.includes(brand.id)" name="i-lucide-check" class="size-3 text-navy-950" />
            </span>
            <span class="min-w-0 flex-1">{{ brand.name }}</span>
          </NuxtLink>
        </li>
      </ul>
    </section>

    <section v-if="filters.priceRanges.length" aria-labelledby="catalog-price-filter-title">
      <div class="mb-2.5 flex items-center gap-2.5">
        <UIcon name="i-lucide-wallet" class="size-4 flex-none text-gold-700" aria-hidden="true" />
        <h3 id="catalog-price-filter-title" class="m-0 text-sm font-bold text-navy-950">Mức giá</h3>
      </div>
      <ul class="m-0 list-none p-0">
        <li v-for="range in filters.priceRanges" :key="range.key" class="mb-1 last:mb-0">
          <NuxtLink
            class="group flex min-h-10 items-center gap-2.5 rounded-btn px-2.5 text-sm no-underline transition-colors hover:bg-gold-50 focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-gold-700"
            :class="selected.priceKeys.includes(range.key) ? 'bg-gold-50 font-semibold text-navy-950' : 'text-slate-700'"
            :to="filterLink({ price: toggledKeys(selected.priceKeys, range.key) })"
            :aria-current="selected.priceKeys.includes(range.key) ? 'true' : undefined"
          >
            <span class="grid size-[18px] flex-none place-items-center rounded-chip border border-slate-400 bg-white transition-colors group-hover:border-gold-600" :class="selected.priceKeys.includes(range.key) ? 'border-transparent bg-gold-400' : ''" aria-hidden="true">
              <UIcon v-if="selected.priceKeys.includes(range.key)" name="i-lucide-check" class="size-3 text-navy-950" />
            </span>
            <span class="min-w-0 flex-1">{{ range.label }}</span>
          </NuxtLink>
        </li>
      </ul>
    </section>
  </aside>
</template>
