<script setup lang="ts">
import MigrationGalleryPreviewPage from '~/pages/migration-gallery-preview.vue'
import galleryPublicRoute from '~/middleware/gallery-public-route'

definePageMeta({ layout: false, middleware: galleryPublicRoute })
</script>

<template><MigrationGalleryPreviewPage /></template>
