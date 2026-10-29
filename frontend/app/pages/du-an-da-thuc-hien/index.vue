<script setup lang="ts">
import MigrationProjectsPreviewPage from '~/pages/migration-projects-preview.vue'
import projectsPublicRoute from '~/middleware/projects-public-route'

definePageMeta({ layout: false, middleware: projectsPublicRoute })
</script>

<template><MigrationProjectsPreviewPage /></template>
