<script setup lang="ts">
import type { PublicProject, PublicProjectBenefit } from '#shared/contracts/public-projects'

defineProps<{ project: PublicProject; benefits: PublicProjectBenefit[]; label: string; viewLabel: string }>()
const links = useMigrationLinks()
</script>

<template>
  <!--
    Panel phải nền navy-800 đặc (không phải navy-deep/950 như CSS preview cũ
    khai — đo pixel bên trong panel cho giá trị gần đúng #0a2251). Số "01" góc
    trên phải là trang trí tĩnh: DTO chỉ có một `featured` duy nhất (không phải
    mảng), không phải số thứ tự động từ dữ liệu.
  -->
  <article class="mb-12 mt-2.5 grid grid-cols-1 nav:grid-cols-[minmax(0,1.1fr)_minmax(280px,0.9fr)]">
    <div class="relative aspect-[4/3] overflow-hidden bg-[#edf1f5] nav:aspect-auto" :class="{ 'grid place-items-center text-slate-500': !project.image }">
      <img v-if="project.image" :src="project.image" :alt="project.title" class="h-full w-full object-cover" loading="lazy" decoding="async">
      <span v-else aria-hidden="true">—</span>

      <span v-if="project.category" class="absolute left-3 top-3 flex items-center gap-1.5 rounded-chip bg-navy-800 px-2.5 py-1.5 text-[11px] font-bold text-white">
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 3 3 10h2v9h5v-5h4v5h5v-9h2Z" /></svg>
        {{ project.category.name }}
      </span>
      <span class="absolute bottom-3 left-3 rounded-chip bg-gold-500 px-3 py-1.5 text-[11px] font-bold text-navy-950">
        {{ label }}
      </span>
    </div>

    <div class="relative bg-navy-800 p-6 text-white nav:p-[clamp(24px,5vw,72px)]">
      <h2 class="mb-3 text-2xl font-black leading-tight">{{ project.title }}</h2>
      <p v-if="project.address" class="mb-1 flex items-center gap-1.5 text-sm text-blue-100">
        <svg class="h-3.5 w-3.5 flex-none" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a7 7 0 0 0-7 7c0 5 7 13 7 13s7-8 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z" /></svg>
        {{ project.address }}
      </p>
      <p v-if="project.description" class="mb-4 mt-3 max-w-[60ch] leading-relaxed text-blue-100">
        {{ project.description }}
      </p>

      <ul class="my-4 list-none space-y-2 p-0">
        <li v-for="benefit in benefits" :key="benefit.label" class="flex items-start gap-2 text-sm text-blue-100">
          <img v-if="benefit.image" :src="benefit.image" alt="" class="mt-0.5 h-[22px] w-[22px] flex-none object-contain" loading="lazy">
          <svg v-else class="mt-0.5 h-3.5 w-3.5 flex-none text-gold-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.1 2 5.8 13h4.8l-.7 9L18.2 11h-4.8l-.3-9Z" /></svg>
          {{ benefit.label }}
        </li>
      </ul>

      <a
        class="inline-flex items-center gap-2 rounded-chip bg-hdr-gold px-5 py-3 font-bold text-navy-950 no-underline
               transition-colors hover:bg-hdr-gold-hover"
        :href="links.project(project.slug)"
      >
        {{ viewLabel }} <span aria-hidden="true">→</span>
      </a>
    </div>
  </article>
</template>
