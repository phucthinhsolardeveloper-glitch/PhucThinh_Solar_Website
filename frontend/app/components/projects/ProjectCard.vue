<script setup lang="ts">
import type { PublicProject } from '#shared/contracts/public-projects'

defineProps<{ project: PublicProject; viewLabel: string }>()
const links = useMigrationLinks()
</script>

<template>
  <article class="min-w-0">
    <a :href="links.project(project.slug)" class="group block text-navy-800 no-underline">
      <div class="relative aspect-[4/3] overflow-hidden rounded-chip bg-[#edf1f5]" :class="{ 'grid place-items-center text-slate-500': !project.image }">
        <img v-if="project.image" :src="project.image" :alt="project.title" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.02]" loading="lazy" decoding="async">
        <span v-else aria-hidden="true">—</span>
        <span v-if="project.category" class="absolute left-3 top-3 flex items-center gap-1.5 rounded-chip bg-navy-800 px-2.5 py-1.5 text-[11px] font-bold text-white">
          <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3 3 10h2v9h5v-5h4v5h5v-9h2Z" /></svg>
          {{ project.category.name }}
        </span>
      </div>

      <div class="py-[18px] pb-[26px]">
        <h2 class="m-0 text-lg font-bold leading-snug text-navy-800">{{ project.title }}</h2>
        <p v-if="project.address" class="my-1 flex items-center gap-1.5 text-sm text-slate-500">
          <svg class="h-3 w-3 flex-none" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z" /></svg>
          {{ project.address }}
        </p>
        <p v-if="project.description" class="mb-0 mt-2 line-clamp-3 leading-relaxed text-slate-600">
          {{ project.description }}
        </p>
        <span class="mt-2.5 inline-block text-[13px] font-bold text-gold-700">{{ viewLabel }} <span aria-hidden="true">→</span></span>
      </div>
    </a>
  </article>
</template>
