<script setup lang="ts">
import type { PublicProjectsPayload } from '#shared/contracts/public-projects'
import { isPublicProjectsPayload } from '#shared/contracts/public-projects'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import ProjectCard from '~/components/projects/ProjectCard.vue'
import ProjectsFeatured from '~/components/projects/ProjectsFeatured.vue'
import ProjectsCta from '~/components/projects/ProjectsCta.vue'

const route = useRoute()
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const requestQuery = computed(() => {
  const query: Record<string, string> = {}
  for (const key of ['category', 'page']) if (typeof route.query[key] === 'string' && route.query[key]) query[key] = route.query[key] as string
  return query
})
const { data, error } = await useAsyncData<unknown>('public-projects', () => $fetch('/_migration/projects', { query: requestQuery.value }), { watch: [requestQuery] })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const projects = computed(() => isPublicProjectsPayload(data.value) ? data.value : null)
const requestUrl = useRequestURL()
const pageUrl = (page: number) => {
  const query = new URLSearchParams(requestQuery.value)
  if (page <= 1) query.delete('page'); else query.set('page', String(page))
  const suffix = query.toString()
  return `/du-an-da-thuc-hien/${suffix ? `?${suffix}` : ''}`
}
const pageNumbers = computed(() => {
  if (!projects.value) return []
  const total = Math.ceil(projects.value.pagination.total / projects.value.pagination.perPage)
  const current = projects.value.pagination.page
  const result: (number | '…')[] = []
  for (let page = 1; page <= total; page++) if (page === 1 || page === total || Math.abs(page - current) <= 2) { if (result.length && typeof result[result.length - 1] === 'number' && page > (result[result.length - 1] as number) + 1) result.push('…'); result.push(page) }
  return result
})

useSeoMeta({
  title: () => `${projects.value?.title || 'Dự án đã thực hiện'} — Phúc Thịnh Solar`,
  description: () => projects.value?.intro?.description || 'Khám phá các dự án năng lượng mặt trời Phúc Thịnh Solar đã triển khai.',
  robots: computed(() => projects.value && !error.value ? 'index, follow' : 'noindex, nofollow, noarchive'),
})
useHead(() => ({ link: [{ rel: 'canonical', href: new URL('/du-an-da-thuc-hien/', requestUrl.origin).toString() }] }))
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <main class="pb-20 pt-[18px]">
      <div class="mx-auto w-full max-w-[1766px] px-4">
        <SiteBreadcrumb :items="[{ label: 'Dự án' }]" />

        <div v-if="error || !projects" class="min-h-[30vh] py-6" role="status">
          <h1 class="mb-3 text-3xl font-black tracking-tight">Dự án đã thực hiện</h1>
          <p class="text-slate-500">Không thể tải dữ liệu dự án. Vui lòng thử lại sau.</p>
        </div>

        <template v-else>
          <section v-if="projects.intro?.visible !== false" class="py-7" aria-labelledby="projects-page-title">
            <p v-if="projects.intro?.eyebrow" class="mb-2 text-sm font-semibold uppercase tracking-wide text-gold-700">{{ projects.intro.eyebrow }}</p>
            <h1 id="projects-page-title" class="text-[clamp(32px,5vw,56px)] font-black leading-tight tracking-tight text-navy-950">{{ projects.intro?.title || projects.title }}</h1>
            <p class="mt-4 max-w-3xl text-base leading-7 text-slate-600">{{ projects.intro?.description || 'Khám phá các dự án năng lượng mặt trời Phúc Thịnh Solar đã triển khai.' }}</p>
          </section>
          <h1 v-else class="sr-only">{{ projects.title }}</h1>
          <nav class="mb-6 flex gap-2 overflow-x-auto py-0.5 pb-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" :aria-label="projects.labels.filters">
            <a class="flex min-h-[42px] flex-none items-center rounded-chip px-[13px] no-underline" :class="!projects.category ? 'bg-navy-800 text-white' : 'border border-gray-200 text-navy-800 hover:border-gold-700'" :href="pageUrl(1)">{{ projects.labels.all }}</a>
            <a v-for="category in projects.categories" :key="category.id" class="flex min-h-[42px] flex-none items-center gap-[7px] rounded-chip px-[13px] no-underline" :class="projects.category?.id === category.id ? 'bg-navy-800 text-white' : 'border border-gray-200 text-navy-800 hover:border-gold-700'" :href="`/du-an-da-thuc-hien/?category=${encodeURIComponent(category.slug)}`">
              <img v-if="category.image" :src="category.image" alt="" class="h-6 w-6 object-contain" width="24" height="24">{{ category.name }}
            </a>
          </nav>

          <ProjectsFeatured v-if="projects.featured" :project="projects.featured" :label="projects.labels.featured" :view-label="projects.labels.view" />

          <section class="grid grid-cols-1 gap-x-6 gap-y-8 tb:grid-cols-2 nav:grid-cols-3" :aria-label="projects.title">
            <ProjectCard v-for="project in projects.projects" :key="project.id" :project="project" :view-label="projects.labels.view" />
          </section>

          <p v-if="!projects.featured && !projects.projects.length" class="min-h-[30vh] py-6 text-slate-500">{{ projects.labels.empty }}</p>

          <nav v-if="projects.pagination.total > projects.pagination.perPage" class="mt-8 flex flex-wrap justify-center gap-2" aria-label="Trang dự án">
            <a v-if="projects.pagination.page > 1" class="grid h-[38px] min-w-[38px] place-items-center rounded-full border border-gray-200 text-navy-800 no-underline hover:border-gold-500 hover:bg-gold-400" rel="prev" :href="pageUrl(projects.pagination.page - 1)">‹</a>
            <template v-for="(page, index) in pageNumbers" :key="`${page}-${index}`">
              <span v-if="page === '…'" class="grid h-[38px] min-w-[38px] place-items-center">…</span>
              <a v-else class="grid h-[38px] min-w-[38px] place-items-center rounded-full border text-navy-800 no-underline" :class="page === projects.pagination.page ? 'border-gold-500 bg-gold-400' : 'border-gray-200 hover:border-gold-500 hover:bg-gold-400'" :aria-current="page === projects.pagination.page ? 'page' : undefined" :href="pageUrl(page)">{{ page }}</a>
            </template>
            <a v-if="projects.pagination.page < Math.ceil(projects.pagination.total / projects.pagination.perPage)" class="grid h-[38px] min-w-[38px] place-items-center rounded-full border border-gray-200 text-navy-800 no-underline hover:border-gold-500 hover:bg-gold-400" rel="next" :href="pageUrl(projects.pagination.page + 1)">›</a>
          </nav>

          <ProjectsCta v-if="projects.cta" :cta="projects.cta" :label="projects.labels.cta" />
        </template>
      </div>
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
