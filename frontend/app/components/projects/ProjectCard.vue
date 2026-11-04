<script setup lang="ts">
import type { PublicProject } from '#shared/contracts/public-projects'

defineProps<{ project: PublicProject; viewLabel: string }>()
const links = useMigrationLinks()
</script>

<template>
  <article class="project-tile min-w-0">
    <a :href="links.project(project.slug)" class="project-tile__link group">
      <span class="project-tile__media aspect-[4/3]" :class="{ 'is-empty': !project.image }">
        <img v-if="project.image" :src="project.image" :alt="project.title" loading="lazy" decoding="async">
        <span v-else aria-hidden="true">Chưa có hình ảnh</span>
      </span>
      <span class="project-tile__veil" aria-hidden="true" />
      <span v-if="project.category" class="project-tile__category">
        <img v-if="project.category.image" :src="project.category.image" alt="" class="h-3.5 w-3.5 object-contain brightness-0 invert">
        {{ project.category.name }}
      </span>
      <span class="project-tile__body">
        <h2>{{ project.title }}</h2>
        <span v-if="project.address" class="project-tile__place">
          <UIcon name="i-lucide-map-pin" class="size-4 flex-none text-gold-400" aria-hidden="true" />{{ project.address }}
        </span>
        <span v-if="project.description" class="project-tile__desc">{{ project.description }}</span>
        <span class="project-tile__go">{{ viewLabel }} <span aria-hidden="true">→</span></span>
      </span>
    </a>
  </article>
</template>

<style scoped>
.project-tile { --ease: cubic-bezier(0.22, 1, 0.36, 1); }
.project-tile__link { position: relative; isolation: isolate; display: flex; height: 100%; min-height: 320px; overflow: hidden; border-radius: 8px; background: var(--color-navy-950); color: #fff; text-decoration: none; }
.project-tile__link:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 4px; }
.project-tile__media { position: absolute; inset: 0; z-index: -2; display: grid; width: 100%; height: 100%; aspect-ratio: auto; place-items: center; background: #1a2f55; color: #9fb0c8; font-size: 14px; font-weight: 600; }
.project-tile__media img { width: 100%; height: 100%; object-fit: cover; transition: transform 1s var(--ease); }
.project-tile__link:hover .project-tile__media img, .project-tile__link:focus-visible .project-tile__media img { transform: scale(1.06); }
.project-tile__veil { position: absolute; inset: 0; z-index: -1; background: linear-gradient(180deg, rgb(6 21 55 / 0%) 35%, rgb(6 21 55 / 92%) 100%); transition: background-color 0.4s ease; }
.project-tile__link:hover .project-tile__veil { background-color: rgb(6 21 55 / 20%); }
.project-tile__category { position: absolute; top: 16px; left: 16px; display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; background: rgb(6 21 55 / 75%); color: #fff; font-size: 13px; font-weight: 700; backdrop-filter: blur(6px); }
.project-tile__body { display: flex; width: 100%; flex-direction: column; gap: 8px; margin-top: auto; padding: clamp(20px, 2vw, 32px); }
.project-tile__body h2 { margin: 0; color: #fff; font-size: clamp(19px, 1.5vw, 23px); font-weight: 850; line-height: 1.25; letter-spacing: -0.015em; }
.project-tile__place { display: inline-flex; align-items: center; gap: 6px; color: #d3dcea; font-size: 14.5px; font-weight: 600; }
.project-tile__desc { display: -webkit-box; max-height: 0; overflow: hidden; -webkit-box-orient: vertical; -webkit-line-clamp: 2; color: #c3cfdf; font-size: 15px; line-height: 1.6; opacity: 0; transition: max-height 0.5s var(--ease), opacity 0.35s ease; }
.project-tile__link:hover .project-tile__desc, .project-tile__link:focus-visible .project-tile__desc { max-height: 3.4em; opacity: 1; }
.project-tile__go { display: inline-flex; align-items: center; gap: 8px; margin-top: 4px; color: var(--color-gold-300); font-size: 14.5px; font-weight: 800; }
.project-tile__go > span { transition: transform 0.35s var(--ease); }
.project-tile__link:hover .project-tile__go > span { transform: translateX(6px); }
@media (hover: none) { .project-tile__desc { max-height: 3.4em; opacity: 1; } }
@media (prefers-reduced-motion: reduce) {
  .project-tile__media img, .project-tile__desc, .project-tile__go > span { transition: none; }
  .project-tile__link:hover .project-tile__media img { transform: none; }
}
</style>
