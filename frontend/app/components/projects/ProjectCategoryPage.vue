<script setup lang="ts">
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import type { PublicProjectsPayload } from '#shared/contracts/public-projects'
import { isPublicProjectsPayload } from '#shared/contracts/public-projects'
import { PROJECTS_PUBLIC_PATH } from '#shared/public-route-gate'
import { editorialPublicResponseStatus, editorialPublicRouteRobots } from '#shared/editorial-public-route'
import { publicSeoDescription, publicSeoTitle } from '#shared/public-seo'
import { projectCategoryCanonicalPath, projectCategoryPage } from '#shared/project-category-route'
import ProjectWall from '~/components/projects/ProjectWall.vue'
import ProjectFilters from '~/components/projects/ProjectFilters.vue'
import ProjectsFeatured from '~/components/projects/ProjectsFeatured.vue'
import ProjectsCta from '~/components/projects/ProjectsCta.vue'

const route = useRoute()
const config = useRuntimeConfig()
const { openConsultation } = useStorefrontModal()
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : '')
const canonicalPath = computed(() => projects.value?.category?.canonicalPath || projectCategoryCanonicalPath(slug.value))
const page = computed(() => projectCategoryPage(route.query.p))
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_api/site', { default: () => fallbackSite })
const { data, error, pending } = await useAsyncData<unknown>(
  () => `project-category-${slug.value}-${page.value}`,
  () => $fetch('/_api/projects', { query: { category: slug.value, page: String(page.value) } }),
  { watch: [slug, page] },
)
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const matchingProjects = computed<PublicProjectsPayload | null>(() => {
  const payload = isPublicProjectsPayload(data.value) ? data.value : null
  return payload?.category?.slug === slug.value ? payload : null
})
const lastResolvedProjects = shallowRef<PublicProjectsPayload | null>(null)
watch(matchingProjects, (payload) => { if (payload) lastResolvedProjects.value = payload }, { immediate: true })
const projects = computed(() => matchingProjects.value ?? (pending.value ? lastResolvedProjects.value : null))
const errorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
const responseStatus = editorialPublicResponseStatus(true, errorStatus, !projects.value)
if (import.meta.server && responseStatus) setResponseStatus(responseStatus, responseStatus === 404 ? 'Not Found' : 'Bad Gateway')

const currentPage = computed(() => projects.value?.pagination.page ?? page.value)
const totalPages = computed(() => projects.value ? Math.max(1, Math.ceil(projects.value.pagination.total / projects.value.pagination.perPage)) : 1)
const paginationItems = usePaginationItems(currentPage, totalPages)
// The first project leads the hero, as on the main projects page, so the wall does not repeat it.
// A group without projects borrows the main page's lead photo, so its hero looks the same.
const { data: allProjects } = await useAsyncData<unknown>('project-category-backdrop', () => $fetch('/_api/projects'), { lazy: true })
const heroBackdrop = computed(() => {
  const payload = isPublicProjectsPayload(allProjects.value) ? allProjects.value : null
  return payload?.featured?.image || payload?.projects[0]?.image || undefined
})
const heroProject = computed(() => currentPage.value === 1 ? projects.value?.projects[0] ?? null : null)
const wallProjects = computed(() => heroProject.value ? projects.value?.projects.slice(1) ?? [] : projects.value?.projects ?? [])
const categoryTitle = computed(() => {
  const title = projects.value?.category?.name || projects.value?.intro.title || ''
  if (!title) return 'Danh mục dự án'
  return /^Dự án(?:\s|$)/i.test(title) ? title : `Dự án ${title}`
})
const categoryDescription = computed(() => projects.value?.intro.description || projects.value?.category?.description || '')
const pageTitle = computed(() => publicSeoTitle(
  projects.value?.category?.seo?.title || projects.value?.category?.name || projects.value?.intro.title,
  'Danh mục dự án',
))
const pageDescription = computed(() => projects.value?.category?.seo?.description || categoryDescription.value)
const socialDescription = computed(() => publicSeoDescription(projects.value?.category?.seo?.description, site.value.brand.name))
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
    true,
    config.public.projectsPublicRouteIndexable,
    !!projects.value && !error.value,
    'p' in route.query,
  )),
})
useHead({ meta: computed(() => pageKeywords.value ? [{ name: 'keywords', content: pageKeywords.value }] : []) })
usePublicSocialMeta({
  enabled: computed(() => !!projects.value && !error.value),
  title: pageTitle,
  description: socialDescription,
  canonicalPath,
  image: computed(() => projects.value?.category?.seo?.image || projects.value?.category?.image || undefined),
})
</script>

<template>
  <div class="text-navy-800">
    <div class="border-b border-slate-200/70 bg-slate-50/80">
      <div class="pts-container">
        <SiteBreadcrumb :items="[
        { label: 'Dự án đã thực hiện', to: PROJECTS_PUBLIC_PATH },
        { label: projects?.category?.name || 'Danh mục dự án' },
      ]" />
      </div>
    </div>
    <main class="pb-12">
      <section v-if="error || !projects" class="pts-container min-h-[40vh] py-10" :role="responseStatus === 404 ? 'status' : 'alert'">
        <h1 class="mb-3 text-3xl font-black tracking-tight">{{ responseStatus === 404 ? 'Không tìm thấy danh mục dự án' : 'Tạm thời không thể tải danh mục dự án' }}</h1>
        <p class="mb-5 text-slate-600">{{ responseStatus === 404 ? 'Danh mục này hiện không tồn tại hoặc đã ẩn.' : 'Nguồn nội dung hiện không khả dụng.' }}</p>
        <div class="flex flex-wrap gap-3">
          <NuxtLink :to="PROJECTS_PUBLIC_PATH" class="inline-flex min-h-11 items-center rounded-btn pts-gold-fill px-5 py-2.5 text-sm font-bold no-underline ">Xem tất cả dự án</NuxtLink>
          <button type="button" class="inline-flex min-h-11 cursor-pointer items-center rounded-btn bg-gold-500 px-5 py-2.5 text-sm font-extrabold text-navy-950 hover:bg-gold-400" @click="openConsultation('Tư vấn dự án điện mặt trời')">Nhận tư vấn công trình →</button>
        </div>
      </section>

      <template v-else>
        <ProjectsFeatured
          v-if="currentPage === 1"
          :title="categoryTitle"
          :description="categoryDescription"
          :project="heroProject"
          :benefits="projects.featuredBenefits"
          :label="projects.labels.featured"
          :view-label="projects.labels.view"
          :backdrop="heroBackdrop"
          :empty-label="projects.labels.empty"
          :cta-label="projects.labels.cta"
          @cta="openConsultation(`Dự án ${categoryTitle}`)"
        />
        <header v-else class="pts-container pt-8">
          <h1 class="m-0 text-[clamp(30px,3.6vw,48px)] font-black tracking-tight text-navy-950">{{ categoryTitle }}</h1>
          <p class="mt-2 mb-0 text-slate-500">Trang {{ currentPage }}</p>
        </header>

        <div data-results class="pts-container pt-8 nav:pt-12">
          <ProjectFilters :categories="projects.categories" :selected="projects.category" :all-label="projects.labels.all" :filters-label="projects.labels.filters" />

        <ProjectWall :projects="wallProjects" :view-label="projects.labels.view" :label="categoryTitle" />
        <div v-if="!projects.projects.length" class="flex min-h-[230px] flex-col items-center justify-center px-5 py-9 text-center text-slate-600" role="status">
          <UIcon name="i-lucide-images" class="mb-3 size-8 text-gold-600" aria-hidden="true" />
          <p class="mb-3 text-base">{{ projects.labels.empty }}</p>
          <button type="button" class="font-extrabold text-navy-800 underline decoration-navy-300 underline-offset-4 transition-colors hover:text-gold-700" @click="openConsultation(`Dự án ${categoryTitle}`)">
            {{ projects.labels.cta }} →
          </button>
        </div>

        <nav v-if="totalPages > 1" class="mt-10 flex flex-wrap justify-center gap-2" aria-label="Phân trang dự án">
          <SitePaginationButton
            v-if="currentPage > 1"
            :to="pageLink(currentPage - 1)"
            aria-label="Trang trước"
          >Trước</SitePaginationButton>
          <template v-for="item in paginationItems" :key="item.key">
            <span v-if="item.page === null" class="text-gold-700" aria-hidden="true">…</span>
            <SitePaginationButton v-else-if="item.page === currentPage" active>{{ item.page }}</SitePaginationButton>
            <SitePaginationButton v-else :to="pageLink(item.page)" :aria-label="`Trang ${item.page}`">{{ item.page }}</SitePaginationButton>
          </template>
          <SitePaginationButton
            v-if="currentPage < totalPages"
            :to="pageLink(currentPage + 1)"
            aria-label="Trang tiếp theo"
          >Sau</SitePaginationButton>
        </nav>

        <ProjectsCta v-if="projects.cta" :cta="projects.cta" :label="projects.labels.cta" />
        </div>
      </template>
    </main>
  </div>
</template>
