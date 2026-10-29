<script setup lang="ts">
import MigrationPolicyPreviewPage from '~/pages/migration-policy-preview.vue'
import policyDetailPublicRoute from '~/middleware/policy-detail-public-route'

definePageMeta({ layout: false, middleware: policyDetailPublicRoute })
</script>

<template><MigrationPolicyPreviewPage /></template>
