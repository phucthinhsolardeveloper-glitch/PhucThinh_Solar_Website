<script setup lang="ts">
import CatalogPage from '~/components/catalog/CatalogPage.vue'
import catalogPublicRoute from '~/middleware/catalog-public-route'

definePageMeta({
  layout: false,
  middleware: catalogPublicRoute,
})

</script>

<template>
  <CatalogPage />
</template>
