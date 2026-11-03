<script setup lang="ts">
import type { PublicProject, PublicProjectBenefit } from '#shared/contracts/public-projects'

defineProps<{
  project: PublicProject
  benefits: PublicProjectBenefit[]
  label: string
  viewLabel: string
}>()
const links = useMigrationLinks()
</script>

<template>
  <article class="project-stage relative mb-12 overflow-hidden bg-navy-950 text-white">
    <a class="project-stage__link group block focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold-500" :href="links.project(project.slug)" :aria-label="`${viewLabel}: ${project.title}`">
      <div class="project-stage__visual relative overflow-hidden bg-slate-200">
        <img
          v-if="project.image"
          :src="project.image"
          :alt="project.title"
          class="project-stage__image block aspect-[4/3] w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.025] motion-reduce:transform-none motion-reduce:transition-none"
          loading="eager"
          decoding="async"
        >
        <div v-else class="project-stage__image grid aspect-[4/3] place-items-center bg-slate-200 px-6 text-center text-navy-800">
          Chưa có hình ảnh đại diện cho dự án
        </div>
        <div class="project-stage__veil absolute inset-0" aria-hidden="true" />
      </div>

      <div class="project-stage__content z-10 flex flex-col gap-4 p-6 nav:p-12">
        <span class="inline-flex w-fit items-center gap-2 text-xs font-extrabold uppercase tracking-[0.16em] text-gold-300">
          <span class="h-px w-8 bg-gold-400" aria-hidden="true" />{{ label }}
        </span>
        <h2 class="m-0 max-w-[18ch] text-[clamp(30px,4.2vw,58px)] font-black leading-[1.05] tracking-tight text-white">{{ project.title }}</h2>
        <p v-if="project.address" class="m-0 flex items-center gap-2 text-sm font-semibold text-white/85">
          <UIcon name="i-lucide-map-pin" class="size-4 flex-none text-gold-300" aria-hidden="true" />
          {{ project.address }}
        </p>
        <p v-if="project.description" class="m-0 max-w-[56ch] text-base leading-relaxed text-white/85">{{ project.description }}</p>
        <ul v-if="benefits.length" class="m-0 flex list-none flex-wrap gap-x-5 gap-y-2 p-0 text-sm text-white/85">
          <li v-for="benefit in benefits" :key="benefit.label" class="flex items-center gap-2">
            <img v-if="benefit.image" :src="benefit.image" alt="" class="size-4 object-contain" loading="lazy">
            <UIcon v-else name="i-lucide-zap" class="size-3.5 flex-none text-gold-300" aria-hidden="true" />
            {{ benefit.label }}
          </li>
        </ul>
        <span class="mt-1 inline-flex w-fit items-center gap-3 border-b border-gold-400 pb-2 text-sm font-extrabold text-white transition-colors group-hover:text-gold-300">
          {{ viewLabel }} <span aria-hidden="true" class="transition-transform group-hover:translate-x-1 motion-reduce:transform-none motion-reduce:transition-none">→</span>
        </span>
      </div>
    </a>
  </article>
</template>

<style scoped>
.project-stage {
  aspect-ratio: 4 / 3;
}

.project-stage__visual {
  position: absolute;
  inset: 0;
}

.project-stage__image {
  height: 100%;
}

.project-stage__veil {
  background: linear-gradient(90deg, rgb(6 21 55 / 88%) 0%, rgb(6 21 55 / 54%) 48%, rgb(6 21 55 / 8%) 100%), linear-gradient(0deg, rgb(6 21 55 / 72%) 0%, transparent 62%);
  pointer-events: none;
}

.project-stage__content {
  position: absolute;
  inset: auto 0 0;
  max-width: 820px;
}

@media (max-width: 760px) {
  .project-stage {
    aspect-ratio: auto;
  }

  .project-stage__visual {
    position: relative;
    inset: auto;
  }

  .project-stage__image {
    height: auto;
  }

  .project-stage__veil {
    background: linear-gradient(0deg, rgb(6 21 55 / 92%) 0%, rgb(6 21 55 / 50%) 60%, rgb(6 21 55 / 4%) 100%);
  }

  .project-stage__content {
    position: relative;
    inset: auto;
    max-width: none;
    background: #061537;
  }
}

@media (prefers-reduced-motion: reduce) {
  .project-stage__image {
    transition: none;
  }
}
</style>
