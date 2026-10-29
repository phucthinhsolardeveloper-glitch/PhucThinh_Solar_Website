<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'
import type { PublicSearchPayload } from '#shared/contracts/public-search'

type SearchFilters = NonNullable<PublicSearchPayload['filters']>

const props = defineProps<{
  filters: SearchFilters
  selectedBrandIds: number[]
  selectedPriceKeys: string[]
  filterLink: (filters: { brandIds: number[]; priceKeys: string[] }) => RouteLocationRaw
}>()

const router = useRouter()

function toggleBrand(id: number, event: Event) {
  const checked = (event.target as HTMLInputElement).checked
  const brandIds = checked
    ? [...new Set([...props.selectedBrandIds, id])]
    : props.selectedBrandIds.filter((selected) => selected !== id)
  return router.push(props.filterLink({ brandIds, priceKeys: props.selectedPriceKeys }))
}

function togglePrice(key: string, event: Event) {
  const checked = (event.target as HTMLInputElement).checked
  const priceKeys = checked
    ? [...new Set([...props.selectedPriceKeys, key])]
    : props.selectedPriceKeys.filter((selected) => selected !== key)
  return router.push(props.filterLink({ brandIds: props.selectedBrandIds, priceKeys }))
}

function clearFilters() {
  return router.push(props.filterLink({ brandIds: [], priceKeys: [] }))
}
</script>

<template>
  <aside class="rounded-chip bg-slate-100 p-5" aria-label="Bộ lọc kết quả tìm kiếm">
    <h2 class="m-0 mb-4 text-xl font-black text-navy-950">BỘ LỌC</h2>

    <fieldset v-if="filters.brands.length" class="m-0 border-0 p-0">
      <legend class="mb-3 text-sm font-bold text-navy-950">Thương hiệu</legend>
      <ul class="m-0 list-none p-0">
        <li v-for="brand in filters.brands" :key="brand.id" class="mb-2.5 last:mb-0">
          <label class="flex cursor-pointer items-center gap-2.5 text-[#20344b]">
            <input
              class="h-[18px] w-[18px] flex-none accent-navy-950"
              type="checkbox"
              :checked="selectedBrandIds.includes(brand.id)"
              @change="toggleBrand(brand.id, $event)"
            >
            <span>{{ brand.name }}</span>
          </label>
        </li>
      </ul>
    </fieldset>

    <hr v-if="filters.brands.length && filters.priceRanges.length" class="my-5 border-slate-200">

    <fieldset v-if="filters.priceRanges.length" class="m-0 border-0 p-0">
      <legend class="mb-3 text-sm font-bold text-navy-950">Mức giá</legend>
      <ul class="m-0 list-none p-0">
        <li v-for="range in filters.priceRanges" :key="range.key" class="mb-2.5 last:mb-0">
          <label class="flex cursor-pointer items-center gap-2.5 text-[#20344b]">
            <input
              class="h-[18px] w-[18px] flex-none accent-navy-950"
              type="checkbox"
              :checked="selectedPriceKeys.includes(range.key)"
              @change="togglePrice(range.key, $event)"
            >
            <span>{{ range.label }}</span>
          </label>
        </li>
      </ul>
    </fieldset>

    <button
      v-if="selectedBrandIds.length || selectedPriceKeys.length"
      class="mt-5 min-h-11 rounded-chip px-1 text-sm text-[#20344b] underline underline-offset-2 hover:text-navy-950"
      type="button"
      @click="clearFilters"
    >
      Xóa bộ lọc
    </button>
  </aside>
</template>
