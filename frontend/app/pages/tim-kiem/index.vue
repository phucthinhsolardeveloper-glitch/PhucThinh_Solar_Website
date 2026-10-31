<script setup lang="ts">
import SearchResultsPage from '~/components/catalog/SearchResultsPage.vue'
definePageMeta({
  layout: false,
  middleware: 'search-public-route',
})

</script>

<template>
  <SearchResultsPage />
</template>
