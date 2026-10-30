<script setup lang="ts">
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import type { PublicProjectsPayload } from '#shared/contracts/public-projects'
import { isPublicProjectsPayload } from '#shared/contracts/public-projects'
import { isRuntimeFlagEnabled, PROJECTS_PUBLIC_PATH } from '#shared/public-route-gate'
import { editorialPublicResponseStatus, editorialPublicRouteRobots } from '#shared/editorial-public-route'
import { projectCategoryCanonicalPath, projectCategoryPage } from '#shared/project-category-route'
import ProjectCard from '~/components/projects/ProjectCard.vue'
import ProjectsCta from '~/components/projects/ProjectsCta.vue'

const route = useRoute()
const config = useRuntimeConfig()
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : '')
const canonicalPath = computed(() => projects.value?.category?.canonicalPath || projectCategoryCanonicalPath(slug.value))
const page = computed(() => projectCategoryPage(route.query.p))
const categoryRouteEnabled = computed(() => isRuntimeFlagEnabled(config.public.projectsPublicRouteEnabled))
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data, error } = await useAsyncData<unknown>(
  () => `project-category-${slug.value}-${page.value}`,
  () => $fetch('/_migration/projects', { query: { category: slug.value, page: String(page.value) } }),
  { watch: [slug, page] },
)
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const projects = computed<PublicProjectsPayload | null>(() => {
  const payload = isPublicProjectsPayload(data.value) ? data.value : null
  return payload?.category?.slug === slug.value ? payload : null
})
const errorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
const responseStatus = editorialPublicResponseStatus(categoryRouteEnabled.value, errorStatus, !projects.value)
if (import.meta.server && responseStatus) setResponseStatus(responseStatus, responseStatus === 404 ? 'Not Found' : 'Bad Gateway')

const currentPage = computed(() => projects.value?.pagination.page ?? page.value)
const totalPages = computed(() => projects.value ? Math.max(1, Math.ceil(projects.value.pagination.total / projects.value.pagination.perPage)) : 1)
const paginationItems = usePaginationItems(currentPage, totalPages)
const categoryTitle = computed(() => {
  const title = projects.value?.category?.name || projects.value?.intro.title || ''
  if (!title) return 'Danh mục dự án'
  return /^Dự án(?:\s|$)/i.test(title) ? title : `Dự án ${title}`
})
const categoryDescription = computed(() => projects.value?.intro.description || projects.value?.category?.description || '')
const pageTitle = computed(() => projects.value?.category?.seo?.title || categoryTitle.value)
const pageDescription = computed(() => projects.value?.category?.seo?.description || categoryDescription.value)
const pageKeywords = computed(() => projects.value?.category?.seo?.keywords || '')

function pageLink(nextPage: number) {
  return {
    path: route.path,
    query: nextPage > 1 ? { p: String(nextPage) } : {},
  }
}

useSeoMeta({
  title: pageTitle,
  description: pageDescription,
  robots: computed(() => editorialPublicRouteRobots(
    categoryRouteEnabled.value,
    config.public.projectsPublicRouteIndexable,
    !!projects.value && !error.value,
    'p' in route.query,
  )),
})
useHead({ meta: computed(() => pageKeywords.value ? [{ name: 'keywords', content: pageKeywords.value }] : []) })
usePublicSocialMeta({
  enabled: computed(() => categoryRouteEnabled.value && !!projects.value && !error.value),
  title: pageTitle,
  description: pageDescription,
  canonicalPath,
  image: computed(() => projects.value?.category?.seo?.image || projects.value?.category?.image || undefined),
})
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />
    <main class="mx-auto w-full max-w-[1766px] px-4 pb-12">
      <SiteBreadcrumb :items="[
        { label: 'Dự án đã thực hiện', to: PROJECTS_PUBLIC_PATH },
        { label: projects?.category?.name || 'Danh mục dự án' },
      ]" />

      <section v-if="error || !projects" class="min-h-[40vh] py-10" :role="responseStatus === 404 ? 'status' : 'alert'">
        <h1 class="mb-3 text-3xl font-black tracking-tight">{{ responseStatus === 404 ? 'Không tìm thấy danh mục dự án' : 'Tạm thời không thể tải danh mục dự án' }}</h1>
        <p class="text-slate-600">{{ responseStatus === 404 ? 'Danh mục này hiện không tồn tại hoặc đã ẩn.' : 'Nguồn nội dung hiện không khả dụng.' }}</p>
      </section>

      <template v-else>
        <header class="my-7 max-w-[980px]">
          <h1 class="mb-3 text-[clamp(32px,5vw,56px)] font-black leading-tight tracking-tight text-navy-950">{{ categoryTitle }}</h1>
          <p v-if="categoryDescription" class="m-0 text-lg leading-relaxed text-slate-600">{{ categoryDescription }}</p>
        </header>

        <nav class="flex gap-2 overflow-x-auto py-0.5 pb-6 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Danh mục dự án">
          <NuxtLink
            class="flex min-h-11 flex-none items-center whitespace-nowrap rounded-chip border border-gray-200 px-[13px] text-navy-800 no-underline hover:border-gold-700"
            :to="PROJECTS_PUBLIC_PATH"
          >{{ projects.labels.all }}</NuxtLink>
          <NuxtLink
            v-for="category in projects.categories"
            :key="category.id"
            class="flex min-h-11 flex-none items-center gap-2 whitespace-nowrap rounded-chip px-[13px] no-underline"
            :class="category.slug === slug ? 'bg-navy-800 text-white' : 'border border-gray-200 text-navy-800 hover:border-gold-700'"
            :to="category.url"
            :aria-current="category.slug === slug ? 'page' : undefined"
          >
            <img v-if="category.image" :src="category.image" alt="" class="h-6 w-6 object-contain" width="24" height="24">
            {{ category.name }}
          </NuxtLink>
        </nav>

        <section class="grid grid-cols-1 gap-x-6 gap-y-8 tb:grid-cols-2 nav:grid-cols-3" :aria-label="categoryTitle">
          <ProjectCard v-for="project in projects.projects" :key="project.id" :project="project" :view-label="projects.labels.view" />
        </section>
        <p v-if="!projects.projects.length" class="py-6 text-slate-500">{{ projects.labels.empty }}</p>

        <nav v-if="totalPages > 1" class="mt-10 flex flex-wrap gap-2" aria-label="Phân trang dự án">
          <NuxtLink
            v-if="currentPage > 1"
            class="inline-grid min-h-11 min-w-11 place-items-center rounded-chip border border-gray-200 px-3 py-2 text-navy-800 no-underline transition-colors hover:border-navy-800"
            :to="pageLink(currentPage - 1)"
            aria-label="Trang trước"
          >Trước</NuxtLink>
          <template v-for="item in paginationItems" :key="item.key">
            <span v-if="item.page === null" class="inline-grid min-h-11 min-w-11 place-items-center px-2 py-2 text-slate-500" aria-hidden="true">…</span>
            <span v-else-if="item.page === currentPage" class="inline-grid min-h-11 min-w-11 place-items-center rounded-chip border border-navy-800 bg-navy-800 px-3 py-2 text-white" aria-current="page">{{ item.page }}</span>
            <NuxtLink v-else class="inline-grid min-h-11 min-w-11 place-items-center rounded-chip border border-gray-200 px-3 py-2 text-navy-800 no-underline transition-colors hover:border-navy-800" :to="pageLink(item.page)" :aria-label="`Trang ${item.page}`">{{ item.page }}</NuxtLink>
          </template>
          <NuxtLink
            v-if="currentPage < totalPages"
            class="inline-grid min-h-11 min-w-11 place-items-center rounded-chip border border-gray-200 px-3 py-2 text-navy-800 no-underline transition-colors hover:border-navy-800"
            :to="pageLink(currentPage + 1)"
            aria-label="Trang tiếp theo"
          >Sau</NuxtLink>
        </nav>

        <ProjectsCta v-if="projects.cta" :cta="projects.cta" :label="projects.labels.cta" />
      </template>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>
