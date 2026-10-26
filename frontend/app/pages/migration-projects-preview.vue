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
  <div class="projects-preview"><SiteHeader :site="site" /><main class="projects-preview__main"><div class="pts-container"><SiteBreadcrumb :items="[{ label: 'Dự án' }]" /><div v-if="error || !projects" class="projects-fallback" role="status"><h1>Dự án — internal preview</h1><p>Không thể tải dữ liệu CMS trong môi trường hiện tại. Preview không hiển thị dữ liệu dự án giả.</p></div><template v-else><h1>{{ projects.title }}</h1><ProjectFilters :categories="projects.categories" :selected="projects.category" :all-label="projects.labels.all" :filters-label="projects.labels.filters" /><ProjectsFeatured v-if="projects.featured" :project="projects.featured" :label="projects.labels.featured" :view-label="projects.labels.view" /><section class="projects-grid" :aria-label="projects.title"><ProjectCard v-for="project in projects.projects" :key="project.id" :project="project" :view-label="projects.labels.view" /></section><p v-if="!projects.featured && !projects.projects.length" class="projects-empty">{{ projects.labels.empty }}</p><ProjectsCta v-if="projects.cta" :cta="projects.cta" :label="projects.labels.cta" /></template></div></main><SiteFooter :site="site" /></div>
</template>
<style>@import '~/assets/css/projects-preview.css';</style>
