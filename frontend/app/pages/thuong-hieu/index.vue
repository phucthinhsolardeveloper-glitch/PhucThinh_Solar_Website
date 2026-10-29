<script setup lang="ts">
import CatalogPage from '~/components/catalog/CatalogPage.vue'
import brandRootPublicRoute from '~/middleware/brand-root-public-route'

definePageMeta({ layout: false, middleware: brandRootPublicRoute })
</script>

<template><CatalogPage /></template>
