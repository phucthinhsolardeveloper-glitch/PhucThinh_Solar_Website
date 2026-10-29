<script setup lang="ts">
import MigrationVideoPreviewPage from '~/pages/migration-video-preview.vue'
import videoPublicRoute from '~/middleware/video-public-route'

definePageMeta({ layout: false, middleware: videoPublicRoute })
</script>

<template><MigrationVideoPreviewPage /></template>
