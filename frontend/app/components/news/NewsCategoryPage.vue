<script setup lang="ts">
import type { PublicNewsPayload } from '#shared/contracts/public-news'
import { isPublicNewsPayload } from '#shared/contracts/public-news'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { editorialPublicResponseStatus, editorialPublicRouteRobots } from '#shared/editorial-public-route'
import { publicSeoDescription, publicSeoTitle } from '#shared/public-seo'
import NewsCard from '~/components/news/NewsCard.vue'
import NewsCta from '~/components/news/NewsCta.vue'
import NewsMasthead from '~/components/news/NewsMasthead.vue'
import NewsFilters from '~/components/news/NewsFilters.vue'

const props = defineProps<{ slugOverride?: string }>()
const route = useRoute()
const config = useRuntimeConfig()
const { openConsultation } = useStorefrontModal()
const slug = computed(() => props.slugOverride || (typeof route.params.slug === 'string' ? route.params.slug : ''))
const searchInput = ref(typeof route.query.q === 'string' ? route.query.q : '')
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data, error, pending } = await useAsyncData<unknown>(
  () => `public-news-category-${slug.value}-${String(route.query.p || '1')}-${String(route.query.q || '')}`,
  () => $fetch('/_migration/news', { query: {
    category: slug.value,
    ...(typeof route.query.p === 'string' ? { p: route.query.p } : {}),
    ...(typeof route.query.q === 'string' ? { q: route.query.q } : {}),
  } }),
  { watch: [slug, () => route.query.p, () => route.query.q] },
)
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const matchingNews = computed<PublicNewsPayload | null>(() => {
  const payload = isPublicNewsPayload(data.value) ? data.value : null
  return payload?.category?.slug === slug.value ? payload : null
})
const lastResolvedNews = shallowRef<PublicNewsPayload | null>(null)
watch(matchingNews, (payload) => { if (payload) lastResolvedNews.value = payload }, { immediate: true })
const news = computed(() => matchingNews.value ?? (pending.value ? lastResolvedNews.value : null))
const category = computed(() => news.value?.category || null)
watch(() => route.query.q, (value) => { searchInput.value = typeof value === 'string' ? value : '' })
function submitSearch() {
  const query = { ...route.query }
  const keyword = searchInput.value.trim()
  delete query.p
  if (keyword) query.q = keyword
  else delete query.q
  return navigateTo({ path: route.path, query })
}
const errorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
const responseStatus = editorialPublicResponseStatus(true, errorStatus, !news.value)
if (import.meta.server && responseStatus) setResponseStatus(responseStatus, responseStatus === 404 ? 'Not Found' : 'Bad Gateway')
const hasFilters = computed(() => Object.keys(route.query).length > 0)
const pageUrl = (page: number) => {
  const query = new URLSearchParams()
  if (page > 1) query.set('p', String(page))
  if (typeof route.query.q === 'string' && route.query.q) query.set('q', route.query.q)
  return `${route.path}${query.toString() ? `?${query}` : ''}`
}
const pageNumbers = computed(() => {
  if (!news.value) return []
  const total = Math.ceil(news.value.pagination.total / news.value.pagination.perPage)
  const current = news.value.pagination.page
  const result: (number | '…')[] = []
  for (let page = 1; page <= total; page++) {
    if (page === 1 || page === total || Math.abs(page - current) <= 2) {
      if (result.length && typeof result[result.length - 1] === 'number' && page > (result[result.length - 1] as number) + 1) result.push('…')
      result.push(page)
    }
  }
  return result
})
const pageTitle = computed(() => publicSeoTitle(category.value?.seo?.title || category.value?.name, 'Kiến thức'))
const pageDescription = computed(() => category.value?.seo?.description || category.value?.description || '')
const socialDescription = computed(() => publicSeoDescription(pageDescription.value, site.value.brand.name))
const pageKeywords = computed(() => category.value?.seo?.keywords || '')

useSeoMeta({
  title: pageTitle,
  description: pageDescription,
  robots: computed(() => editorialPublicRouteRobots(true, config.public.newsPublicRouteIndexable, !!news.value && news.value.pagination.total > 0 && !error.value, hasFilters.value)),
})
useHead({ meta: computed(() => pageKeywords.value ? [{ name: 'keywords', content: pageKeywords.value }] : []) })
usePublicSocialMeta({
  enabled: computed(() => !!news.value && !error.value),
  title: pageTitle,
  description: socialDescription,
  canonicalPath: computed(() => category.value?.canonicalPath || `/${slug.value}`),
  image: computed(() => category.value?.seo?.image || category.value?.image || undefined),
})
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />
    <div class="border-b border-slate-200/70 bg-slate-50/80">
      <div class="pts-container">
        <SiteBreadcrumb :items="[{ label: 'Kiến thức', to: '/tin-tuc/' }, { label: category?.name || 'Danh mục bài viết' }]" />
      </div>
    </div>
    <main class="pts-container pb-16" :aria-busy="pending">
      <section v-if="error || !news || !category" class="min-h-[45vh] py-12" :role="responseStatus === 404 ? 'status' : 'alert'">
        <h1 class="mb-3 text-3xl font-black tracking-tight">{{ responseStatus === 404 ? 'Không tìm thấy danh mục bài viết' : 'Tạm thời không thể tải danh mục bài viết' }}</h1>
        <p class="mb-5 text-slate-500">{{ responseStatus === 404 ? 'Danh mục này hiện không tồn tại hoặc đã ẩn.' : 'Nguồn nội dung hiện không khả dụng.' }}</p>
        <div class="flex flex-wrap gap-3">
          <NuxtLink to="/tin-tuc/" class="inline-flex min-h-11 items-center rounded-[4px] bg-navy-900 px-5 py-2.5 text-sm font-bold text-white no-underline hover:bg-navy-800">Về trang Kiến thức</NuxtLink>
          <button type="button" class="inline-flex min-h-11 cursor-pointer items-center rounded-[4px] bg-gold-500 px-5 py-2.5 text-sm font-extrabold text-navy-950 hover:bg-gold-400" @click="openConsultation('Tư vấn kỹ thuật')">Nhận tư vấn kỹ thuật →</button>
        </div>
      </section>

      <template v-else>
        <NewsMasthead
          :title="category.name"
          :description="category.description"
        />

        <div class="pt-6 mb-[clamp(24px,2.6vw,36px)] nav:pt-8">
          <NewsFilters :categories="news.categories" :selected="category" :all-label="news.labels.all" :filters-label="news.labels.results" />
        </div>

        <section v-if="news.articles.length" class="grid grid-cols-1 gap-x-5 gap-y-6 tb:grid-cols-2 nav:grid-cols-3" :aria-label="news.labels.results">
          <NewsCard v-for="article in news.articles" :key="article.id" :article="article" :read-label="news.labels.read" :read-more-label="news.labels.readMore" />
        </section>
        <div v-else class="rounded-[4px] border-l-[4px] border-gold-500 bg-slate-50 px-6 py-10 text-center text-slate-600" role="status">
          <h2 class="mb-2 text-xl font-black text-navy-900">{{ news.labels.empty }}</h2>
          <p class="mx-auto mb-5 max-w-lg text-sm leading-relaxed text-slate-600">
            Bạn có thể xóa từ khóa tìm kiếm hiện tại hoặc xem toàn bộ chuyên mục Kiến thức điện mặt trời.
          </p>
          <div class="flex flex-wrap items-center justify-center gap-3">
            <button v-if="route.query.q" type="button" class="inline-flex min-h-10 cursor-pointer items-center rounded-[4px] bg-navy-900 px-4 py-2 text-xs font-bold text-white hover:bg-navy-800" @click="searchInput = ''; submitSearch()">Xóa bộ lọc tìm kiếm</button>
            <NuxtLink to="/tin-tuc/" class="inline-flex min-h-10 items-center rounded-[4px] border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-navy-800 no-underline hover:border-gold-500">Xem tất cả bài viết</NuxtLink>
          </div>
        </div>

        <nav v-if="news.pagination.total > news.pagination.perPage" class="mt-7 flex flex-wrap justify-center gap-2" :aria-label="news.labels.pages">
          <SitePaginationButton v-if="news.pagination.page > 1" :href="pageUrl(news.pagination.page - 1)" rel="prev" aria-label="Trang trước">‹</SitePaginationButton>
          <template v-for="(page, index) in pageNumbers" :key="`${page}-${index}`">
            <span v-if="page === '…'" class="text-gold-700">…</span>
            <SitePaginationButton v-else-if="page === news.pagination.page" active>{{ page }}</SitePaginationButton>
            <SitePaginationButton v-else :href="pageUrl(page)" :aria-label="`Trang ${page}`">{{ page }}</SitePaginationButton>
          </template>
          <SitePaginationButton v-if="news.pagination.page < Math.ceil(news.pagination.total / news.pagination.perPage)" :href="pageUrl(news.pagination.page + 1)" rel="next" aria-label="Trang sau">›</SitePaginationButton>
        </nav>

        <NewsCta v-if="news.cta" :cta="news.cta" :phone-label="news.labels.ctaPhone" />
      </template>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>
