<script setup lang="ts">
import type { PublicProject } from '#shared/contracts/public-projects'
defineProps<{ project: PublicProject; viewLabel: string }>()
</script>
<template>
  <article class="projects-card">
    <a :href="project.url" class="projects-card__link">
      <div class="projects-card__media" :class="{ 'is-placeholder': !project.image }">
        <img v-if="project.image" :src="project.image" :alt="project.title" loading="lazy" decoding="async">
        <span v-else aria-hidden="true">—</span>
      </div>
      <div class="projects-card__body">
        <h2>{{ project.title }}</h2>
        <p v-if="project.address" class="projects-card__meta">{{ project.address }}</p>
        <p v-if="project.category" class="projects-card__category">{{ project.category.name }}</p>
        <p v-if="project.description" class="projects-card__description">{{ project.description }}</p>
        <span class="projects-card__action">{{ viewLabel }} <span aria-hidden="true">→</span></span>
      </div>
    </a>
  </article>
</template>
