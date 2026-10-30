<script setup lang="ts">
import { isPublicRouteResolveResult, isPublicRouteResolveSlug } from '#shared/contracts/public-route-resolve'
import { bareSlugPublicPath, isRuntimeFlagEnabled, phpPublicRouteUrl } from '#shared/public-route-gate'
import { editorialPublicResponseStatus } from '#shared/editorial-public-route'
import { publicBareSlugDisposition } from '#shared/public-bare-slug-route'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import MigrationNewsDetailPage from '~/pages/migration-news-detail-preview.vue'
import MigrationProjectDetailPage from '~/pages/migration-project-detail-preview.vue'
import MigrationGalleryDetailPage from '~/pages/migration-gallery-detail-preview.vue'
import ProjectCategoryPage from '~/components/projects/ProjectCategoryPage.vue'
import NewsCategoryPage from '~/components/news/NewsCategoryPage.vue'
import SolutionCategoryPage from '~/components/solutions/SolutionCategoryPage.vue'

definePageMeta({ layout: false })

const route = useRoute()
const config = useRuntimeConfig()
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const slug = typeof route.params.slug === 'string' ? route.params.slug : ''
const legacyAliases: Record<string, string> = {
  've-chung-toi': '/gioi-thieu/',
  'he-thong-dai-ly': '/lien-he/',
  product: '/san-pham/',
}
const aliasTarget = legacyAliases[slug]
const reservedRouteRoots = new Set(['ho-tro'])
const canonicalBarePath = (() => {
  if (!isPublicRouteResolveSlug(slug) || reservedRouteRoots.has(slug)) return false
  try { return bareSlugPublicPath(slug) } catch { return false }
})()
const pathIsSafe = typeof canonicalBarePath === 'string'
const pathIsCanonical = pathIsSafe && route.path === canonicalBarePath
const barePathWithoutSlash = pathIsSafe ? `/${slug}` : false
const pathCanResolve = pathIsCanonical || (typeof barePathWithoutSlash === 'string' && route.path === barePathWithoutSlash)
const detailRoutesEnabled = isRuntimeFlagEnabled(config.public.newsDetailPublicRouteEnabled)
  || isRuntimeFlagEnabled(config.public.newsPublicRouteEnabled)
  || isRuntimeFlagEnabled(config.public.solutionsPublicRouteEnabled)
  || isRuntimeFlagEnabled(config.public.projectDetailPublicRouteEnabled)
  || isRuntimeFlagEnabled(config.public.projectsPublicRouteEnabled)
  || isRuntimeFlagEnabled(config.public.galleryDetailPublicRouteEnabled)
const { data: resolutionData, error: resolutionError } = await useAsyncData<unknown>(
  `public-route-resolve-${slug}`,
  () => aliasTarget || !pathIsSafe || !pathCanResolve || !detailRoutesEnabled
    ? Promise.resolve(null)
    : $fetch('/_migration/public-route-resolve', { query: { slug } }),
)
const activeDetail = ref<'news' | 'news-category' | 'solution-category' | 'project' | 'project-category' | 'gallery' | null>(null)
const pageState = ref<'not-found' | 'unavailable' | 'redirecting' | 'ready'>('not-found')
const resolutionStatus = (resolutionError.value as { statusCode?: unknown } | null)?.statusCode

function setHttpStatus(status: 404 | 502) {
  if (import.meta.server) setResponseStatus(status, status === 404 ? 'Not Found' : 'Bad Gateway')
}

if (aliasTarget) {
  pageState.value = 'redirecting'
  await navigateTo(aliasTarget, { redirectCode: 301 })
} else if (!pathIsSafe) {
  pageState.value = 'not-found'
  setHttpStatus(404)
} else if (!detailRoutesEnabled && typeof canonicalBarePath === 'string') {
  if (!pathIsCanonical) {
    pageState.value = 'redirecting'
    await navigateTo(canonicalBarePath, { redirectCode: 301 })
  } else {
    pageState.value = 'redirecting'
    await navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, canonicalBarePath), { external: true, redirectCode: 307 })
  }
} else if (!pathCanResolve && typeof canonicalBarePath === 'string') {
  pageState.value = 'redirecting'
  await navigateTo(canonicalBarePath, { redirectCode: 301 })
} else if (resolutionError.value) {
  const status = editorialPublicResponseStatus(true, resolutionStatus, true)
  if (status) setHttpStatus(status)
  pageState.value = status === 404 ? 'not-found' : 'unavailable'
} else if (!isPublicRouteResolveResult(resolutionData.value) || resolutionData.value.slug !== slug) {
  pageState.value = 'unavailable'
  setHttpStatus(502)
} else {
  const disposition = publicBareSlugDisposition(resolutionData.value, {
    newsDetailPublicRouteEnabled: config.public.newsDetailPublicRouteEnabled,
    newsPublicRouteEnabled: config.public.newsPublicRouteEnabled,
    solutionsPublicRouteEnabled: config.public.solutionsPublicRouteEnabled,
    projectDetailPublicRouteEnabled: config.public.projectDetailPublicRouteEnabled,
    projectsPublicRouteEnabled: config.public.projectsPublicRouteEnabled,
    galleryDetailPublicRouteEnabled: config.public.galleryDetailPublicRouteEnabled,
  })
  if (disposition.mode === 'legacy') {
    pageState.value = 'redirecting'
    await navigateTo(phpPublicRouteUrl(config.public.phpPublicOrigin, disposition.path), { external: true, redirectCode: 307 })
  } else if (disposition.mode === 'canonical') {
    pageState.value = 'redirecting'
    await navigateTo(disposition.path, { redirectCode: disposition.statusCode })
  } else if (['project-category', 'news-category', 'solution-category'].includes(disposition.kind) && route.path !== resolutionData.value.canonicalPath) {
    pageState.value = 'redirecting'
    await navigateTo(resolutionData.value.canonicalPath, { redirectCode: 301 })
  } else {
    activeDetail.value = disposition.kind
    pageState.value = 'ready'
  }
}

if (!activeDetail.value) useSeoMeta({ robots: 'noindex, nofollow, noarchive' })

const detailPage = computed(() => {
  if (activeDetail.value === 'news') return MigrationNewsDetailPage
  if (activeDetail.value === 'news-category') return NewsCategoryPage
  if (activeDetail.value === 'solution-category') return SolutionCategoryPage
  if (activeDetail.value === 'project') return MigrationProjectDetailPage
  if (activeDetail.value === 'project-category') return ProjectCategoryPage
  if (activeDetail.value === 'gallery') return MigrationGalleryDetailPage
  return null
})
</script>

<template>
  <component :is="detailPage" v-if="detailPage" />
  <div v-else class="min-h-screen bg-white text-navy-800">
    <SiteHeader :site="site" />
    <main class="pts-container min-h-[45vh] py-16" :aria-busy="pageState === 'redirecting'">
      <div v-if="pageState === 'unavailable'" role="alert">
        <h1 class="mb-3 text-3xl font-black">Tạm thời không thể tải trang</h1>
        <p class="text-slate-600">Nguồn nội dung hiện không khả dụng.</p>
      </div>
      <div v-else-if="pageState === 'not-found'" role="status">
        <h1 class="mb-3 text-3xl font-black">Không tìm thấy trang</h1>
      </div>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>
