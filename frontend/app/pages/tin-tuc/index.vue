<script setup lang="ts">
import MigrationNewsPreviewPage from '~/components/views/NewsView.vue'
definePageMeta({ layout: false, middleware: 'news-public-route' })
</script>

<template><MigrationNewsPreviewPage /></template>
