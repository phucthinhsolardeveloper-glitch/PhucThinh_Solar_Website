<script setup lang="ts">
import type { PublicProject } from '#shared/contracts/public-projects'

defineProps<{ project: PublicProject; viewLabel: string }>()
const links = useMigrationLinks()
</script>

<template>
  <article class="min-w-0">
    <a :href="links.project(project.slug)" class="group flex h-full flex-col overflow-hidden text-navy-800 no-underline">
      <div class="relative aspect-[4/3] overflow-hidden rounded-btn bg-[#edf1f5]" :class="{ 'grid place-items-center text-slate-500': !project.image }">
        <img v-if="project.image" :src="project.image" :alt="project.title" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.02]" loading="lazy" decoding="async">
        <span v-else aria-hidden="true">—</span>
        <span v-if="project.category" class="absolute left-3 top-3 flex items-center gap-1.5 rounded-btn bg-navy-800/90 px-2.5 py-1 text-[11px] font-bold text-white">
          <img v-if="project.category.image" :src="project.category.image" alt="" class="h-3.5 w-3.5 object-contain brightness-0 invert">
          <svg v-else class="h-3.5 w-3.5 text-gold-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3 3 10h2v9h5v-5h4v5h5v-9h2Z" /></svg>
          {{ project.category.name }}
        </span>
      </div>

      <div class="flex flex-1 flex-col pt-4 pb-1">
        <h2 class="m-0 text-base font-extrabold leading-snug text-navy-800 transition-colors group-hover:text-gold-700 tb:text-lg">{{ project.title }}</h2>
        <div v-if="project.address || project.category" class="my-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
          <span v-if="project.address" class="inline-flex items-center gap-1.5">
            <svg class="h-3 w-3 flex-none text-gold-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z" /></svg>
            {{ project.address }}
          </span>
          <span v-if="project.category" class="inline-flex items-center gap-1.5">
            <svg class="h-3 w-3 flex-none text-gold-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="m13 2-9 12h7l-1 8 10-13h-7l1-7Z" /></svg>
            {{ project.category.name }}
          </span>
        </div>
        <p v-if="project.description" class="mb-0 mt-1 line-clamp-2 text-sm leading-relaxed text-slate-500">
          {{ project.description }}
        </p>
        <span class="mt-4 inline-flex items-center gap-1 text-[13px] font-extrabold text-gold-700">
          <span>{{ viewLabel }}</span>
          <span aria-hidden="true">→</span>
        </span>
      </div>
    </a>
  </article>
</template>
