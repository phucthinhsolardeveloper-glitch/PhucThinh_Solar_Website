<script setup lang="ts">
import MigrationSolutionDetailPreviewPage from '~/pages/migration-solution-detail-preview.vue'
// One stable page instance across slugs so the switcher can animate instead of remounting.
definePageMeta({ layout: false, middleware: 'solution-detail-public-route', key: 'solution-detail' })
</script>

<template><MigrationSolutionDetailPreviewPage /></template>
