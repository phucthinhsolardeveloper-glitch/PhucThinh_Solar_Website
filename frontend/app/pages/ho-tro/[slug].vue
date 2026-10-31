<script setup lang="ts">
import MigrationPolicyPreviewPage from '~/pages/migration-policy-preview.vue'
definePageMeta({ layout: false, middleware: 'policy-detail-public-route' })
</script>

<template><MigrationPolicyPreviewPage /></template>
