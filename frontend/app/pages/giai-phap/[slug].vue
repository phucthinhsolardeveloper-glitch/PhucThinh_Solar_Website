<script setup lang="ts">
import MigrationSolutionDetailPreviewPage from '~/pages/migration-solution-detail-preview.vue'
import solutionDetailPublicRoute from '~/middleware/solution-detail-public-route'

definePageMeta({ layout: false, middleware: solutionDetailPublicRoute })
</script>

<template><MigrationSolutionDetailPreviewPage /></template>
