<script setup lang="ts">
import type { PublicProject } from '#shared/contracts/public-projects'

defineProps<{ project: PublicProject; viewLabel: string }>()
const links = usePublicLinks()
</script>

<template>
  <article class="project-card min-w-0">
    <a :href="links.project(project.slug)" class="project-card__link group">
      <span class="project-card__media pts-panel-sheen aspect-[4/3]" :class="{ 'is-empty': !project.image }">
        <img v-if="project.image" :src="optimizedImage(project.image, 800, 600)" :alt="project.title" loading="lazy" decoding="async">
        <span v-else aria-hidden="true">Chưa có hình ảnh</span>
      </span>
      <span class="project-card__body">
        <span v-if="project.category" class="project-card__category">{{ project.category.name }}</span>
        <h2>{{ project.title }}</h2>
        <span v-if="project.address" class="project-card__place">
          <UIcon name="i-lucide-map-pin" class="size-4 flex-none text-gold-600" aria-hidden="true" />{{ project.address }}
        </span>
        <span class="project-card__go">{{ viewLabel }} <span aria-hidden="true">→</span></span>
      </span>
    </a>
  </article>
</template>

<style scoped>
.project-card__link { display: flex; height: 100%; flex-direction: column; color: var(--color-navy-900); text-decoration: none; }
.project-card__link:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 4px; border-radius: var(--radius-sm); }
.project-card__media { display: grid; overflow: hidden; place-items: center; border-radius: var(--radius-sm); background: #edf1f5; color: #64748b; font-size: 14px; font-weight: 600; }
.project-card__media img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.7s cubic-bezier(0.22, 1, 0.36, 1); }
.project-card__link:hover .project-card__media img { transform: scale(1.04); }
.project-card__body { display: flex; flex: 1; flex-direction: column; gap: 6px; padding-top: 16px; }
.project-card__category { color: var(--color-gold-700); font-size: 13.5px; font-weight: 700; }
.project-card__body h2 { margin: 0; color: var(--color-navy-950); font-size: clamp(18px, 1.35vw, 21px); font-weight: 800; line-height: 1.32; transition: color 0.2s ease; }
.project-card__link:hover h2 { color: var(--color-gold-700); }
.project-card__place { display: inline-flex; align-items: center; gap: 6px; color: #64748b; font-size: 14.5px; }
.project-card__go { margin-top: auto; padding-top: 6px; color: var(--color-navy-800); font-size: 14.5px; font-weight: 800; }
.project-card__go > span { display: inline-block; transition: transform 0.3s ease; }
.project-card__link:hover .project-card__go > span { transform: translateX(5px); }
@media (prefers-reduced-motion: reduce) {
  .project-card__media img, .project-card__go > span { transition: none; }
  .project-card__link:hover .project-card__media img { transform: none; }
}
</style>
