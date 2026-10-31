<script setup lang="ts">
import MigrationGalleryPreviewPage from '~/pages/migration-gallery-preview.vue'
definePageMeta({ layout: false, middleware: 'gallery-public-route' })
</script>

<template><MigrationGalleryPreviewPage /></template>
