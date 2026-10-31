<script setup lang="ts">
import type { PublicProjectsPayload } from '#shared/contracts/public-projects'
import { isPublicProjectsPayload } from '#shared/contracts/public-projects'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { PROJECTS_PUBLIC_PATH } from '#shared/public-route-gate'
import { editorialPublicHubResponseStatus, editorialPublicRouteRobots } from '#shared/editorial-public-route'
import ProjectCard from '~/components/projects/ProjectCard.vue'
import ProjectFilters from '~/components/projects/ProjectFilters.vue'
import ProjectsFeatured from '~/components/projects/ProjectsFeatured.vue'
import ProjectsCta from '~/components/projects/ProjectsCta.vue'

const route = useRoute()
const config = useRuntimeConfig()
const isPublicRoute = computed(() => route.path === PROJECTS_PUBLIC_PATH)
const publicProjectsEnabled = computed(() => isPublicRoute.value)
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const category = computed(() => typeof route.query.category === 'string' ? route.query.category : undefined)
// PHP uses `p` on the public URL; the read-only DTO endpoint names it `page`.
const page = computed(() => {
  const value = route.query.p
  if (typeof value !== 'string' || !/^[1-9][0-9]*$/.test(value)) return 1
  const parsed = Number(value)
  return Number.isSafeInteger(parsed) && parsed <= 10000 ? parsed : 1
})
const requestQuery = computed(() => ({
  ...(category.value ? { category: category.value } : {}),
  page: String(page.value),
}))
const { data, error } = await useAsyncData<unknown>(
  () => `migration-projects-${JSON.stringify(requestQuery.value)}`,
  () => $fetch('/_migration/projects', { query: requestQuery.value }),
  { watch: [requestQuery] },
)
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const projects = computed(() => isPublicProjectsPayload(data.value) ? data.value : null)
const currentPage = computed(() => projects.value?.pagination.page ?? page.value)
const totalPages = computed(() => projects.value ? Math.max(1, Math.ceil(projects.value.pagination.total / projects.value.pagination.perPage)) : 1)
const paginationItems = usePaginationItems(currentPage, totalPages)
const pageTitle = computed(() => projects.value?.seo?.title || projects.value?.title || 'Dự án')
const pageDescription = computed(() => projects.value?.seo?.description || projects.value?.intro.description || '')
const pageKeywords = computed(() => projects.value?.seo?.keywords || '')
const errorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
const responseStatus = editorialPublicHubResponseStatus(publicProjectsEnabled.value, errorStatus, projects.value === null)
if (import.meta.server && responseStatus) setResponseStatus(responseStatus, responseStatus === 404 ? 'Not Found' : 'Bad Gateway')

function pageLink(nextPage: number) {
  return {
    path: isPublicRoute.value ? PROJECTS_PUBLIC_PATH : '/migration-projects-preview',
    query: {
      ...(category.value ? { category: category.value } : {}),
      ...(nextPage > 1 ? { p: String(nextPage) } : {}),
    },
  }
}

useSeoMeta({
  title: () => isPublicRoute.value ? pageTitle.value : 'Dự án — internal preview',
  description: () => pageDescription.value,
  robots: computed(() => editorialPublicRouteRobots(
    isPublicRoute.value,
    config.public.projectsPublicRouteIndexable,
    !!projects.value && !error.value,
    ['category', 'p', 'page'].some((key) => key in route.query),
  )),
})
useHead({ meta: computed(() => pageKeywords.value ? [{ name: 'keywords', content: pageKeywords.value }] : []) })
usePublicSocialMeta({
  enabled: computed(() => isPublicRoute.value && !!projects.value && !error.value),
  title: pageTitle,
  description: pageDescription,
  canonicalPath: '/du-an-da-thuc-hien/',
  image: computed(() => projects.value?.seo?.image || undefined),
})
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <div class="border-b border-slate-200/70 bg-slate-50/80">
      <div class="pts-container">
        <SiteBreadcrumb :items="[{ label: 'Dự án' }]" />
      </div>
    </div>

    <main class="pts-container pb-20 pt-4">
      <div v-if="error || !projects" class="min-h-[30vh] py-6" role="status">
          <h1 class="mb-3 text-3xl font-black tracking-tight">{{ isPublicRoute ? 'Dự án' : 'Dự án — internal preview' }}</h1>
          <p class="text-slate-500">Không thể tải dữ liệu CMS trong môi trường hiện tại. {{ isPublicRoute ? 'Nội dung sẽ hiển thị khi CMS khả dụng.' : 'Preview không hiển thị dữ liệu dự án giả.' }}</p>
        </div>

        <template v-else>
          <header v-if="projects.intro.visible" class="my-7 max-w-[980px]">
            <h1 class="mb-3 text-[clamp(32px,5vw,56px)] font-black leading-tight tracking-tight text-navy-950">{{ projects.intro.title }}</h1>
            <p v-if="projects.intro.description" class="m-0 text-lg leading-relaxed text-slate-600">{{ projects.intro.description }}</p>
          </header>
          <h1 v-else class="sr-only">{{ projects.title }}</h1>

          <ProjectFilters :categories="projects.categories" :selected="projects.category" :all-label="projects.labels.all" :filters-label="projects.labels.filters" />

          <ProjectsFeatured v-if="projects.featured" :project="projects.featured" :benefits="projects.featuredBenefits" :label="projects.labels.featured" :view-label="projects.labels.view" />

          <section class="grid grid-cols-1 gap-x-6 gap-y-8 tb:grid-cols-2 nav:grid-cols-3" :aria-label="projects.title">
            <ProjectCard v-for="project in projects.projects" :key="project.id" :project="project" :view-label="projects.labels.view" />
          </section>

          <nav v-if="totalPages > 1" class="mt-10 flex flex-wrap justify-center gap-2" aria-label="Phân trang dự án">
            <SitePaginationButton
              v-if="currentPage > 1"
              :to="pageLink(currentPage - 1)"
              aria-label="Trang trước"
            >Trước</SitePaginationButton>
            <template v-for="item in paginationItems" :key="item.key">
              <span v-if="item.page === null" class="text-gold-700" aria-hidden="true">…</span>
              <SitePaginationButton
                v-else-if="item.page === currentPage"
                active
                :aria-label="`Trang ${item.page}`"
              >{{ item.page }}</SitePaginationButton>
              <SitePaginationButton
                v-else
                :to="pageLink(item.page)"
                :aria-label="`Trang ${item.page}`"
              >{{ item.page }}</SitePaginationButton>
            </template>
            <SitePaginationButton
              v-if="currentPage < totalPages"
              :to="pageLink(currentPage + 1)"
              aria-label="Trang tiếp theo"
            >Sau</SitePaginationButton>
          </nav>

          <p v-if="!projects.featured && !projects.projects.length" class="py-6 text-slate-500">{{ projects.labels.empty }}</p>

          <ProjectsCta v-if="projects.cta" :cta="projects.cta" :label="projects.labels.cta" />
        </template>
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
