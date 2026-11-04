<script setup lang="ts">
import type { PublicProject } from '#shared/contracts/public-projects'
import ProjectCard from './ProjectCard.vue'

defineProps<{ projects: PublicProject[]; viewLabel: string; label: string }>()
</script>

<template>
  <!-- Uniform grid: reads the same on page 1 or page 20, whatever the count. -->
  <section v-if="projects.length" class="project-grid" :aria-label="label">
    <ProjectCard v-for="project in projects" :key="project.id" :project="project" :view-label="viewLabel" />
  </section>
</template>

<style scoped>
.project-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: clamp(28px, 3vw, 44px) clamp(18px, 2vw, 32px); }
@media (max-width: 1100px) { .project-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 640px) { .project-grid { grid-template-columns: 1fr; } }
</style>
