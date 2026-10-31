<script setup lang="ts">
import ProjectCategoryPage from '~/components/projects/ProjectCategoryPage.vue'
definePageMeta({ layout: false, middleware: 'project-category-public-route' })
</script>

<template>
  <ProjectCategoryPage />
</template>
