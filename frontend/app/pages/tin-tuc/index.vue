<script setup lang="ts">
import MigrationNewsPreviewPage from '~/pages/migration-news-preview.vue'
import newsPublicRoute from '~/middleware/news-public-route'

definePageMeta({ layout: false, middleware: newsPublicRoute })
</script>

<template><MigrationNewsPreviewPage /></template>
