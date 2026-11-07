<script setup lang="ts">
import type { PublicNewsPayload } from '#shared/contracts/public-news'
import { isPublicNewsPayload } from '#shared/contracts/public-news'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { NEWS_PUBLIC_PATH } from '#shared/public-route-gate'
import { editorialPublicHubResponseStatus, editorialPublicRouteRobots } from '#shared/editorial-public-route'
import NewsCard from '~/components/news/NewsCard.vue'
import NewsCta from '~/components/news/NewsCta.vue'
import NewsFeatured from '~/components/news/NewsFeatured.vue'
import NewsMasthead from '~/components/news/NewsMasthead.vue'
import NewsFilters from '~/components/news/NewsFilters.vue'

const route = useRoute()
const config = useRuntimeConfig()
const isPublicRoute = computed(() => route.path === NEWS_PUBLIC_PATH)
const publicPath = computed(() => isPublicRoute.value ? NEWS_PUBLIC_PATH : '/migration-news-preview/')
const robots = computed(() => editorialPublicRouteRobots(
  isPublicRoute.value,
  config.public.newsPublicRouteIndexable,
  !!news.value && !error.value,
  Object.keys(requestQuery.value).length > 0,
))
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_api/site', { default: () => fallbackSite })
const requestQuery = computed(() => {
  const query: Record<string, string> = {}
  for (const key of ['q', 'p', 'category']) if (typeof route.query[key] === 'string' && route.query[key]) query[key] = route.query[key] as string
  return query
})
const { data, error, pending } = await useAsyncData<unknown>('migration-news', () => $fetch('/_api/news', { query: requestQuery.value }), { watch: [requestQuery] })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const news = computed(() => isPublicNewsPayload(data.value) ? data.value : null)
const errorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
const responseStatus = editorialPublicHubResponseStatus(
  isPublicRoute.value,
  errorStatus,
  news.value === null,
)
if (import.meta.server && responseStatus) setResponseStatus(responseStatus, responseStatus === 404 ? 'Not Found' : 'Bad Gateway')
const pageUrl = (page: number) => {
  const query = new URLSearchParams(requestQuery.value)
  if (page <= 1) query.delete('p'); else query.set('p', String(page))
  return `${publicPath.value}${query.toString() ? `?${query}` : ''}`
}
// The magazine block holds a lead + three side stories; topped up from the latest list, never repeated below.
const featuredBlock = computed(() => {
  if (!news.value?.featured.length) return []
  const ids = new Set(news.value.featured.map((article) => article.id))
  const fill = news.value.articles.filter((article) => !ids.has(article.id))
  return [...news.value.featured, ...fill.slice(0, Math.max(0, 4 - news.value.featured.length))]
})
const gridArticles = computed(() => {
  const used = new Set(featuredBlock.value.map((article) => article.id))
  return (news.value?.articles ?? []).filter((article) => !used.has(article.id))
})
const pageNumbers = computed(() => {
  if (!news.value) return []
  const total = Math.ceil(news.value.pagination.total / news.value.pagination.perPage)
  const current = news.value.pagination.page
  const result: (number | '…')[] = []
  for (let page = 1; page <= total; page++) if (page === 1 || page === total || Math.abs(page - current) <= 2) { if (result.length && typeof result[result.length - 1] === 'number' && page > (result[result.length - 1] as number) + 1) result.push('…'); result.push(page) }
  return result
})
const pageTitle = computed(() => isPublicRoute.value
  ? news.value?.seo?.title || news.value?.title || 'Kiến thức'
  : `${news.value?.title || 'Kiến thức'} — internal preview`)
const pageDescription = computed(() => news.value?.seo?.description || news.value?.description || '')
const pageKeywords = computed(() => isPublicRoute.value ? news.value?.seo?.keywords || '' : '')
useSeoMeta({
  title: pageTitle,
  description: pageDescription,
  robots,
})
useHead({ meta: computed(() => pageKeywords.value ? [{ name: 'keywords', content: pageKeywords.value }] : []) })
usePublicSocialMeta({
  enabled: computed(() => isPublicRoute.value && !!news.value && !error.value),
  title: pageTitle,
  description: pageDescription,
  canonicalPath: NEWS_PUBLIC_PATH,
  image: computed(() => news.value?.seo?.image || undefined),
})
</script>

<template>
  <div class="text-navy-800">

    <div class="border-b border-slate-200/70 bg-slate-50/80">
      <div class="pts-container">
        <SiteBreadcrumb :items="[{ label: news?.category?.name || 'Kiến thức' }]" />
      </div>
    </div>

    <main class="pts-container pb-16" :aria-busy="pending">
      <div v-if="!news" class="min-h-[45vh] py-12" :role="error ? 'alert' : 'status'">
          <h1 class="mb-3 text-3xl font-black tracking-tight">{{ isPublicRoute ? 'Kiến thức' : 'Kiến thức — internal preview' }}</h1>
          <p class="text-slate-500">{{ isPublicRoute ? 'Bài viết tạm thời chưa tải được. Vui lòng thử lại sau ít phút.' : 'Không thể tải dữ liệu CMS trong môi trường hiện tại. Preview không hiển thị bài viết giả.' }}</p>
        </div>

        <template v-else>
          <p v-if="error" class="mb-5 rounded-btn bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">Không thể cập nhật bài viết. Đang giữ kết quả vừa tải trước đó.</p>
          <!--
            PHÁT HIỆN: CSS preview cũ canh giữa (text-align:center) cho tiêu đề
            trang. Ảnh production canh TRÁI, không giới hạn max-width 760px —
            tiêu đề kéo dài theo chiều rộng container như mọi trang khác.
          -->
          <NewsMasthead
            :title="news.title"
            :description="news.description"
          />

          <div data-results class="pt-6 mb-[clamp(24px,2.6vw,36px)] nav:pt-8">
            <NewsFilters :categories="news.categories" :selected="news.category" :all-label="news.labels.all" :filters-label="news.labels.results" :query="requestQuery.q" />
          </div>

          <NewsFeatured :articles="featuredBlock" :label="news.labels.featured" :read-label="news.labels.read" :read-more-label="news.labels.readMore" />

          <p v-if="requestQuery.q" class="-mt-2 mb-[22px] text-slate-500">
            {{ news.labels.results }}: <strong>{{ news.pagination.total }}</strong> — "{{ requestQuery.q }}"
            <a class="inline-flex min-h-11 items-center text-gold-700" :href="publicPath">× {{ news.labels.reset }}</a>
          </p>

          <section v-if="gridArticles.length" class="grid grid-cols-1 gap-x-5 gap-y-6 tb:grid-cols-2 nav:grid-cols-3" :aria-label="news.labels.results">
            <NewsCard v-for="article in gridArticles" :key="article.id" :article="article" :read-label="news.labels.read" :read-more-label="news.labels.readMore" />
          </section>
          <div v-else-if="!featuredBlock.length" class="min-h-[260px] py-[52px] text-center text-slate-500" role="status">
            <h2 class="mb-3 text-navy-800">{{ news.labels.empty }}</h2>
            <a class="inline-flex min-h-11 items-center font-bold text-gold-700" :href="publicPath">{{ news.labels.reset }} →</a>
          </div>

          <nav v-if="news.pagination.total > news.pagination.perPage" class="mt-7 flex flex-wrap justify-center gap-2" :aria-label="news.labels.pages">
            <SitePaginationButton v-if="news.pagination.page > 1" :href="pageUrl(news.pagination.page - 1)" rel="prev" aria-label="Trang trước">‹</SitePaginationButton>
            <template v-for="(page, index) in pageNumbers" :key="`${page}-${index}`">
              <span v-if="page === '…'" class="text-gold-700">…</span>
              <SitePaginationButton
                v-else
                :active="page === news.pagination.page"
                :href="pageUrl(page)"
                :aria-label="`Trang ${page}`"
              >
                {{ page }}
              </SitePaginationButton>
            </template>
            <SitePaginationButton v-if="news.pagination.page < Math.ceil(news.pagination.total / news.pagination.perPage)" :href="pageUrl(news.pagination.page + 1)" rel="next" aria-label="Trang sau">›</SitePaginationButton>
          </nav>

          <NewsCta v-if="news.cta" :cta="news.cta" :phone-label="news.labels.ctaPhone" />
        </template>
    </main>

  </div>
</template>

