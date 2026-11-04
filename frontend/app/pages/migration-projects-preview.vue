<script setup lang="ts">
import type { PublicProjectsPayload } from '#shared/contracts/public-projects'
import { isPublicProjectsPayload } from '#shared/contracts/public-projects'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { PROJECTS_PUBLIC_PATH } from '#shared/public-route-gate'
import { editorialPublicHubResponseStatus, editorialPublicRouteRobots } from '#shared/editorial-public-route'
import ProjectWall from '~/components/projects/ProjectWall.vue'
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

    <main class="pb-20">
      <div v-if="error || !projects" class="pts-container min-h-[30vh] py-6" role="status">
        <h1 class="mb-3 text-3xl font-black tracking-tight">{{ isPublicRoute ? 'Dự án' : 'Dự án — internal preview' }}</h1>
        <p class="text-slate-500">{{ isPublicRoute ? 'Danh sách dự án tạm thời chưa tải được. Vui lòng thử lại sau ít phút.' : 'Không thể tải dữ liệu CMS trong môi trường hiện tại. Preview không hiển thị dữ liệu dự án giả.' }}</p>
      </div>

      <template v-else>
        <ProjectsFeatured
          v-if="currentPage === 1"
          :title="projects.intro.visible ? projects.intro.title : projects.title"
          :description="projects.intro.visible ? projects.intro.description : ''"
          :project="projects.featured"
          :benefits="projects.featuredBenefits"
          :label="projects.labels.featured"
          :view-label="projects.labels.view"
          :backdrop="projects.projects[0]?.image"
        />

        <header v-else class="pts-container pt-8">
          <h1 class="m-0 text-[clamp(30px,3.6vw,48px)] font-black tracking-tight text-navy-950">{{ projects.intro.visible ? projects.intro.title : projects.title }}</h1>
          <p class="mt-2 mb-0 text-slate-500">Trang {{ currentPage }}</p>
        </header>

        <div data-results class="pts-container pt-8 nav:pt-12">
          <ProjectFilters :categories="projects.categories" :selected="projects.category" :all-label="projects.labels.all" :filters-label="projects.labels.filters" />

          <ProjectWall :projects="projects.projects" :view-label="projects.labels.view" :label="projects.title" />

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
        </div>
      </template>
    </main>

    <SiteFooter :site="site" />
  </div>
</template>

