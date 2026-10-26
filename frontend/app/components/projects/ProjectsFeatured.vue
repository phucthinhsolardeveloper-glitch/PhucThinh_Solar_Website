<script setup lang="ts">
import type { PublicProject } from '#shared/contracts/public-projects'
defineProps<{ project: PublicProject; label: string; viewLabel: string }>()
</script>
<template>
  <article class="projects-featured">
    <div class="projects-featured__media" :class="{ 'is-placeholder': !project.image }"><img v-if="project.image" :src="project.image" :alt="project.title" loading="lazy" decoding="async"><span v-else aria-hidden="true">—</span></div>
    <div class="projects-featured__body"><p class="projects-eyebrow">{{ label }}</p><h2>{{ project.title }}</h2><p v-if="project.address" class="projects-card__meta">{{ project.address }}</p><p v-if="project.category" class="projects-card__category">{{ project.category.name }}</p><p v-if="project.description">{{ project.description }}</p><a :href="project.url" class="projects-text-link">{{ viewLabel }} <span aria-hidden="true">→</span></a></div>
  </article>
</template>
