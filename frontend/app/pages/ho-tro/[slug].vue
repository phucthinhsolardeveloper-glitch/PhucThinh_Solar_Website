<script setup lang="ts">
import MigrationPolicyPreviewPage from '~/pages/migration-policy-preview.vue'
// One stable key: moving between policies updates the content instead of rebuilding the page.
definePageMeta({ layout: false, middleware: 'policy-detail-public-route', key: 'policy-detail' })
</script>

<template><MigrationPolicyPreviewPage /></template>
