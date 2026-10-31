<script setup lang="ts">
import MigrationSolutionDetailPreviewPage from '~/pages/migration-solution-detail-preview.vue'
definePageMeta({ layout: false, middleware: 'solution-detail-public-route' })
</script>

<template><MigrationSolutionDetailPreviewPage /></template>
