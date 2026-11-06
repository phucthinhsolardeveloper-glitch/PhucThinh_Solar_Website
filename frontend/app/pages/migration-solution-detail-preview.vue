<script setup lang="ts">
import type { PublicSolutionDetailPayload } from '../../shared/contracts/public-solution-detail'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { SOLUTIONS_PUBLIC_PATH } from '#shared/public-route-gate'
import { editorialPublicResponseStatus, editorialPublicRouteRobots, isSafeEditorialSlug } from '#shared/editorial-public-route'
import SolutionDetailPage from '~/components/solution-detail/SolutionDetailPage.vue'

const route = useRoute()
const config = useRuntimeConfig()
const links = useMigrationLinks()
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : (typeof route.query.slug === 'string' ? route.query.slug : ''))
if (!isSafeEditorialSlug(slug.value)) throw createError({ statusCode: 404, statusMessage: 'Solution not found' })
const isPublicRoute = computed(() => route.path.startsWith(SOLUTIONS_PUBLIC_PATH) && route.path !== SOLUTIONS_PUBLIC_PATH)
const { data: fetched, error } = await useAsyncData<PublicSolutionDetailPayload>(
  () => `migration-solution-detail-${slug.value}`,
  () => {
    if (!isSafeEditorialSlug(slug.value)) throw createError({ statusCode: 404, statusMessage: 'Solution not found' })
    return $fetch('/_migration/solution-detail', { query: { slug: slug.value } })
  },
  { watch: [slug] },
)
// Keep the previous solution on screen while the next one loads (client-side switching).
const data = shallowRef(fetched.value)
watch(fetched, (value) => { if (value) data.value = value })
const responseStatus = editorialPublicResponseStatus(
  isPublicRoute.value,
  (error.value as { statusCode?: unknown } | null)?.statusCode,
  !data.value,
)
if (import.meta.server && responseStatus) setResponseStatus(responseStatus, responseStatus === 404 ? 'Not Found' : 'Bad Gateway')

/**
 * PHÁT HIỆN: trang này trước đó KHÔNG gọi SiteHeader/SiteFooter/SiteBreadcrumb
 * — cùng lỗi đã sửa ở trang danh sách Giải pháp. Ảnh production có đủ header
 * và breadcrumb 3 cấp "Trang chủ > Giải pháp > <tên giải pháp>".
 */
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const pageTitle = computed(() => isPublicRoute.value
  ? data.value?.seo?.title || data.value?.solution.title || 'Giải pháp'
  : 'Giải pháp — internal preview')
const pageDescription = computed(() => isPublicRoute.value
  ? data.value?.seo?.description || data.value?.solution.summary || ''
  : data.value?.solution.summary || '')
const pageKeywords = computed(() => isPublicRoute.value ? data.value?.seo?.keywords || undefined : undefined)
useSeoMeta({
  title: pageTitle,
  description: pageDescription,
  robots: computed(() => editorialPublicRouteRobots(isPublicRoute.value, config.public.solutionDetailPublicRouteIndexable, !!data.value && !error.value)),
})
useHead({ meta: computed(() => pageKeywords.value ? [{ name: 'keywords', content: pageKeywords.value }] : []) })
usePublicSocialMeta({
  enabled: computed(() => isPublicRoute.value && !!data.value && !error.value),
  title: pageTitle,
  description: pageDescription,
  canonicalPath: computed(() => route.path),
  image: computed(() => data.value?.seo?.image || undefined),
})
</script>

<template>
  <div class="text-navy-800">

    <main>
      <div class="border-b border-slate-200/70 bg-slate-50/80">
        <div class="pts-container">
          <SiteBreadcrumb :items="[{ label: 'Giải pháp', to: links.generic(SOLUTIONS_PUBLIC_PATH) }, ...(data ? [{ label: data.solution.title }] : [])]" />
        </div>
      </div>

      <div v-if="error || !data" class="pts-container min-h-[45vh] py-12">
        <h1 class="mb-3 text-3xl font-black tracking-tight">{{ isPublicRoute ? 'Không tìm thấy giải pháp' : 'Giải pháp — internal preview' }}</h1>
        <p class="text-slate-500">{{ isPublicRoute ? 'Nội dung giải pháp tạm thời chưa tải được. Vui lòng kiểm tra lại liên kết hoặc thử lại sau.' : 'Không thể tải dữ liệu CMS trong môi trường hiện tại. Preview không hiển thị nội dung giả.' }}</p>
      </div>

      <SolutionDetailPage v-else :data="data" :hotline="site.brand.hotline" />
    </main>

  </div>
</template>
