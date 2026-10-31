<script setup lang="ts">
import MigrationVideoPreviewPage from '~/pages/migration-video-preview.vue'
definePageMeta({ layout: false, middleware: 'video-public-route' })
</script>

<template><MigrationVideoPreviewPage /></template>
