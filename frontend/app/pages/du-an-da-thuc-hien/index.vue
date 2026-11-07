<script setup lang="ts">
import MigrationProjectsPreviewPage from '~/components/views/ProjectsView.vue'
definePageMeta({ layout: false, middleware: 'projects-public-route' })
</script>

<template><MigrationProjectsPreviewPage /></template>
