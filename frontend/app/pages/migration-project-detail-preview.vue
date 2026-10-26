<script setup lang="ts">
import type { PublicSitePayload } from '#shared/contracts/public-site'
import type { PublicProjectDetailPayload } from '#shared/contracts/public-project-detail'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { isPublicProjectDetailPayload } from '#shared/contracts/public-project-detail'
import ProjectDetailPage from '~/components/project-detail/ProjectDetailPage.vue'
const route = useRoute()
const slug = computed(() => typeof route.query.slug === 'string' ? route.query.slug : '')
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data, error } = await useFetch<unknown>('/_migration/project-detail', { query: computed(() => ({ slug: slug.value })), default: () => null })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const payload = computed<PublicProjectDetailPayload | null>(() => isPublicProjectDetailPayload(data.value) ? data.value : null)
useSeoMeta({ title: computed(() => payload.value ? `${payload.value.project.title} — preview` : 'Project detail preview'), robots: 'noindex, nofollow, noarchive' })
</script>
<template>
  <div class="project-detail-preview"><SiteHeader :site="site" /><main class="pts-container"><SiteBreadcrumb :items="[{ label: 'Dự án' }, ...(payload ? [{ label: payload.project.title }] : [])]" /><div v-if="error || !payload" class="project-detail-fallback" role="status"><h1>Project detail preview</h1><p>Không thể tải dự án CMS hoặc slug chưa hợp lệ. Preview không hiển thị dữ liệu dự án giả.</p></div><ProjectDetailPage v-else :project="payload.project" /></main><SiteFooter :site="site" /></div>
</template>
<style>@import '~/assets/css/project-detail-preview.css';</style>
