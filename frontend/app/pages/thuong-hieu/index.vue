<script setup lang="ts">
import CatalogPage from '~/components/catalog/CatalogPage.vue'
definePageMeta({ layout: false, middleware: 'brand-root-public-route' })
</script>

<template><CatalogPage /></template>
