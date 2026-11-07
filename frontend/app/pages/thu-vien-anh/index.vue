<script setup lang="ts">
import MigrationGalleryPreviewPage from '~/components/views/GalleryView.vue'
definePageMeta({ layout: false, middleware: 'gallery-public-route' })
</script>

<template><MigrationGalleryPreviewPage /></template>
