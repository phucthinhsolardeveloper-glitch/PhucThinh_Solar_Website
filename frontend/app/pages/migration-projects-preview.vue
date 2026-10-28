<script setup lang="ts">
import type { PublicProjectsPayload } from '#shared/contracts/public-projects'
import { isPublicProjectsPayload } from '#shared/contracts/public-projects'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import ProjectCard from '~/components/projects/ProjectCard.vue'
import ProjectFilters from '~/components/projects/ProjectFilters.vue'
import ProjectsFeatured from '~/components/projects/ProjectsFeatured.vue'
import ProjectsCta from '~/components/projects/ProjectsCta.vue'

const route = useRoute()
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const category = typeof route.query.category === 'string' ? route.query.category : undefined
const { data, error } = await useAsyncData<unknown>(`migration-projects-${category || 'all'}`, () => $fetch('/_migration/projects', { query: category ? { category } : undefined }))
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const projects = computed(() => isPublicProjectsPayload(data.value) ? data.value : null)

useSeoMeta({ title: 'Dự án — internal preview', robots: 'noindex, nofollow, noarchive' })
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <main class="pb-20 pt-[18px]">
      <div class="mx-auto w-full max-w-[1766px] px-4">
        <SiteBreadcrumb :items="[{ label: 'Dự án' }]" />

        <div v-if="error || !projects" class="min-h-[30vh] py-6" role="status">
          <h1 class="mb-3 text-3xl font-black tracking-tight">Dự án — internal preview</h1>
          <p class="text-slate-500">Không thể tải dữ liệu CMS trong môi trường hiện tại. Preview không hiển thị dữ liệu dự án giả.</p>
        </div>

        <template v-else>
          <h1 class="my-7 text-[clamp(32px,5vw,56px)] font-black leading-tight tracking-tight text-navy-950">{{ projects.title }}</h1>
          <!--
            CÒN THIẾU: ảnh production có một đoạn mô tả ngay dưới H1 ("Phúc
            Thịnh Solar tự hào đồng hành..."). PublicProjectsPayload không có
            trường description ở cấp payload (chỉ có ở từng project) — không
            bịa, để trống chờ DTO bổ sung.
          -->

          <ProjectFilters :categories="projects.categories" :selected="projects.category" :all-label="projects.labels.all" :filters-label="projects.labels.filters" />

          <ProjectsFeatured v-if="projects.featured" :project="projects.featured" :label="projects.labels.featured" :view-label="projects.labels.view" />

          <section class="grid grid-cols-1 gap-x-6 gap-y-8 tb:grid-cols-2 nav:grid-cols-3" :aria-label="projects.title">
            <ProjectCard v-for="project in projects.projects" :key="project.id" :project="project" :view-label="projects.labels.view" />
          </section>

          <p v-if="!projects.featured && !projects.projects.length" class="min-h-[30vh] py-6 text-slate-500">{{ projects.labels.empty }}</p>

          <ProjectsCta v-if="projects.cta" :cta="projects.cta" :label="projects.labels.cta" />
        </template>
      </div>
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
