<script setup lang="ts">
import type { PublicProductCategoryNode } from '#shared/contracts/public-site'
import CatalogCategoryBranch from './CatalogCategoryBranch.vue'

const props = defineProps<{ categories: PublicProductCategoryNode[]; selected: string }>()
const expanded = ref(Boolean(props.selected))

watch(() => props.selected, (selected) => {
  if (selected) expanded.value = true
})
</script>

<template>
  <nav v-if="categories.length" aria-label="Danh mục sản phẩm">
    <h2 class="mb-4 hidden text-lg font-extrabold text-navy-800 nav:block">Danh mục sản phẩm</h2>
    <button
    class="public-neutral-button flex min-h-11 w-full items-center justify-between rounded-chip px-3 py-2 text-left font-extrabold text-navy-800 nav:hidden"
      type="button"
      :aria-expanded="expanded"
      aria-controls="catalog-category-list"
      @click="expanded = !expanded"
    >
      <span>Danh mục sản phẩm</span>
      <svg class="h-5 w-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5" /></svg>
    </button>
    <ul
      id="catalog-category-list"
      class="m-0 list-none p-0 pt-3 nav:block nav:pt-0"
      :class="expanded ? 'block' : 'hidden'"
    >
      <CatalogCategoryBranch
        v-for="category in categories"
        :key="category.id"
        :node="category"
        :selected="selected"
      />
    </ul>
  </nav>
</template>
