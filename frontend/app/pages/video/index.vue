<script setup lang="ts">
import MigrationVideoPreviewPage from '~/components/views/VideoView.vue'
definePageMeta({ layout: false, middleware: 'video-public-route' })
</script>

<template><MigrationVideoPreviewPage /></template>
