<script setup lang="ts">
import CatalogPage from '~/components/catalog/CatalogPage.vue'
import catalogCategoryPublicRoute from '~/middleware/catalog-category-public-route'
import { productCategorySegmentsFromPublicPath } from '#shared/public-route-gate'

definePageMeta({
  layout: false,
  middleware: catalogCategoryPublicRoute,
})

const route = useRoute()
const categoryPath = computed(() => productCategorySegmentsFromPublicPath(route.path).join('/'))
</script>

<template>
  <CatalogPage :category-path="categoryPath" />
</template>
