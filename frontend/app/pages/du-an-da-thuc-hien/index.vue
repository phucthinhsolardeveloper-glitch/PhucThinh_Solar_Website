<script setup lang="ts">
import MigrationProjectsPreviewPage from '~/pages/migration-projects-preview.vue'
definePageMeta({ layout: false, middleware: 'projects-public-route' })
</script>

<template><MigrationProjectsPreviewPage /></template>
