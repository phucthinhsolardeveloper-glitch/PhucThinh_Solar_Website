<script setup lang="ts">
import SolutionDetailPage from '~/components/views/SolutionDetailView.vue'
// One stable page instance across slugs so the switcher can animate instead of remounting.
definePageMeta({ layout: false, middleware: 'solution-detail-public-route', key: 'solution-detail', viewTransition: false })
</script>

<template><SolutionDetailPage /></template>
