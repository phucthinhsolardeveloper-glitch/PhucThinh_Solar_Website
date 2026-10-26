<script setup lang="ts">
import type { PublicProductCategoryNode } from '#shared/contracts/public-site'
import CatalogCategoryBranch from './CatalogCategoryBranch.vue'

defineProps<{ categories: PublicProductCategoryNode[]; selected: string }>()
</script>

<template>
  <nav v-if="categories.length" class="catalog-category-nav" aria-label="Danh mục sản phẩm">
    <h2>Danh mục sản phẩm</h2>
    <ul>
      <CatalogCategoryBranch v-for="category in categories" :key="category.id" :node="category" :selected="selected" />
    </ul>
  </nav>
</template>
