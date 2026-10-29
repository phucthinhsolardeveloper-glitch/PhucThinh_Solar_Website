<script setup lang="ts">
import ProjectCategoryPage from '~/components/projects/ProjectCategoryPage.vue'
import projectCategoryPublicRoute from '~/middleware/project-category-public-route'

definePageMeta({ layout: false, middleware: projectCategoryPublicRoute })
</script>

<template>
  <ProjectCategoryPage />
</template>
