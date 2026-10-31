<script setup lang="ts">
import CatalogPage from '~/components/catalog/CatalogPage.vue'
definePageMeta({
  layout: false,
  middleware: 'catalog-public-route',
})

</script>

<template>
  <CatalogPage />
</template>
