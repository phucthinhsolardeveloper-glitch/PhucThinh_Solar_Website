<script setup lang="ts">
import ProjectsPage from '~/components/views/ProjectsView.vue'
definePageMeta({ layout: false, middleware: 'projects-public-route' })
</script>

<template><ProjectsPage /></template>
