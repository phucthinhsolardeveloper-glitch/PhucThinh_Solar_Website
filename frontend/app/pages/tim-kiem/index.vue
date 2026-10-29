<script setup lang="ts">
import SearchResultsPage from '~/components/catalog/SearchResultsPage.vue'
import searchPublicRoute from '~/middleware/search-public-route'

definePageMeta({
  layout: false,
  middleware: searchPublicRoute,
})

</script>

<template>
  <SearchResultsPage />
</template>
