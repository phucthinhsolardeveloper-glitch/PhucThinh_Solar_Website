<script setup lang="ts">
import MigrationNewsPreviewPage from '~/pages/migration-news-preview.vue'
definePageMeta({ layout: false, middleware: 'news-public-route' })
</script>

<template><MigrationNewsPreviewPage /></template>
