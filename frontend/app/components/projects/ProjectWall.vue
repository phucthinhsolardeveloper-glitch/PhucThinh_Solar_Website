<script setup lang="ts">
import type { PublicProject } from '#shared/contracts/public-projects'
import ProjectCard from './ProjectCard.vue'

defineProps<{ projects: PublicProject[]; viewLabel: string; label: string }>()
</script>

<template>
  <section v-if="projects.length" class="project-wall" :data-count="projects.length" :aria-label="label">
    <ProjectCard v-for="project in projects" :key="project.id" :project="project" :view-label="viewLabel" />
  </section>
</template>
<style scoped>
/* Wall of work: the first project of every five takes a 2x2 tile, the rest fill around it. */
.project-wall { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); grid-auto-rows: clamp(260px, 22vw, 360px); grid-auto-flow: dense; gap: clamp(12px, 1.2vw, 18px); }
.project-wall > :nth-child(5n + 1) { grid-column: span 2; grid-row: span 2; }
.project-wall[data-count="1"] > * { grid-column: 1 / -1; grid-row: span 2; }
.project-wall[data-count="2"], .project-wall[data-count="4"] { grid-template-columns: repeat(2, minmax(0, 1fr)); grid-auto-rows: clamp(320px, 30vw, 460px); }
.project-wall[data-count="2"] > *, .project-wall[data-count="4"] > * { grid-column: auto; grid-row: auto; }
@media (max-width: 1100px) {
  .project-wall { grid-template-columns: repeat(2, minmax(0, 1fr)); grid-auto-rows: clamp(260px, 34vw, 340px); }
  .project-wall > :nth-child(5n + 1) { grid-column: 1 / -1; grid-row: span 1; }
}
@media (max-width: 640px) {
  .project-wall, .project-wall[data-count="2"], .project-wall[data-count="4"] { grid-template-columns: 1fr; grid-auto-rows: 340px; }
  .project-wall > *, .project-wall > :nth-child(5n + 1) { grid-column: auto; grid-row: auto; }
}
</style>
