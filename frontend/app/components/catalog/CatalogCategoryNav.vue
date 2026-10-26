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
  <nav v-if="categories.length" class="catalog-category-nav" aria-label="Danh mục sản phẩm">
    <h2>Danh mục sản phẩm</h2>
    <button class="catalog-category-nav__toggle" type="button" :aria-expanded="expanded" aria-controls="catalog-category-list" @click="expanded = !expanded">
      <span>Danh mục sản phẩm</span>
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5" /></svg>
    </button>
    <div id="catalog-category-list" class="catalog-category-nav__body" :class="{ 'is-open': expanded }">
      <ul>
        <CatalogCategoryBranch v-for="category in categories" :key="category.id" :node="category" :selected="selected" />
      </ul>
    </div>
  </nav>
</template>
