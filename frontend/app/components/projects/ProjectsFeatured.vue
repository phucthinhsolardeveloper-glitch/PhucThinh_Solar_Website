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
  <article class="project-stage relative mb-12 overflow-hidden rounded-[4px] border-t-[3px] border-gold-500 bg-navy-950 text-white">
    <a
      class="project-stage__link group grid min-[901px]:grid-cols-[minmax(0,1.22fr)_minmax(360px,0.78fr)] no-underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-gold-500"
      :href="links.project(project.slug)"
      :aria-label="`${viewLabel}: ${project.title}`"
    >
      <div class="project-stage__visual relative overflow-hidden bg-slate-200">
        <img
          v-if="project.image"
          :src="project.image"
          :alt="project.title"
          class="project-stage__image block aspect-[4/3] w-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.03] motion-reduce:transform-none motion-reduce:transition-none min-[901px]:h-full"
          loading="eager"
          decoding="async"
        >
        <div v-else class="project-stage__image grid aspect-[4/3] place-items-center bg-slate-200 px-6 text-center text-navy-800 min-[901px]:h-full">
          Chưa có hình ảnh đại diện cho dự án
        </div>
        <span
          v-if="project.category"
          class="absolute left-4 top-4 inline-flex items-center gap-1.5 rounded-[3px] bg-navy-950/90 px-3 py-1.5 text-xs font-bold text-white backdrop-blur-xs"
        >
          <img v-if="project.category.image" :src="project.category.image" alt="" class="size-3.5 object-contain brightness-0 invert">
          <span>{{ project.category.name }}</span>
        </span>
      </div>

      <div class="project-stage__content flex flex-col justify-between gap-6 p-6 tb:p-8 nav:p-10">
        <div class="flex flex-col gap-3.5">
          <span class="inline-flex w-fit items-center gap-2 text-sm font-bold text-gold-300">
            <span class="h-px w-7 bg-gold-400" aria-hidden="true" />{{ label }}
          </span>
          <h2 class="m-0 text-[clamp(26px,3vw,40px)] font-black leading-[1.12] tracking-tight text-white transition-colors group-hover:text-gold-300">
            {{ project.title }}
          </h2>
          <p v-if="project.address" class="m-0 flex items-center gap-2 text-sm font-semibold text-blue-100">
            <UIcon name="i-lucide-map-pin" class="size-4 flex-none text-gold-400" aria-hidden="true" />
            <span>Khu vực triển khai: <strong class="text-white">{{ project.address }}</strong></span>
          </p>
          <p v-if="project.description" class="m-0 text-[15px] leading-relaxed text-blue-100/90">{{ project.description }}</p>
        </div>

        <div class="flex flex-col gap-5 border-t border-white/15 pt-5">
          <ul v-if="benefits.length" class="m-0 grid list-none gap-2.5 p-0 text-sm text-blue-100">
            <li v-for="benefit in benefits" :key="benefit.label" class="flex items-center gap-2.5">
              <span class="grid size-6 flex-none place-items-center rounded-[3px] bg-gold-500/15 text-gold-300">
                <img v-if="benefit.image" :src="benefit.image" alt="" class="size-3.5 object-contain" loading="lazy">
                <UIcon v-else name="i-lucide-check" class="size-3.5" aria-hidden="true" />
              </span>
              <span>{{ benefit.label }}</span>
            </li>
          </ul>

          <span class="inline-flex min-h-11 w-fit items-center gap-2.5 rounded-[4px] bg-gold-500 px-5 py-2.5 text-sm font-extrabold text-navy-950 transition-colors group-hover:bg-gold-400">
            <span>{{ viewLabel }}</span>
            <span aria-hidden="true" class="transition-transform group-hover:translate-x-1 motion-reduce:transform-none motion-reduce:transition-none">→</span>
          </span>
        </div>
      </div>
    </a>
  </article>
</template>

<style scoped>
@media (prefers-reduced-motion: reduce) {
  .project-stage__image {
    transition: none;
  }
}
</style>
