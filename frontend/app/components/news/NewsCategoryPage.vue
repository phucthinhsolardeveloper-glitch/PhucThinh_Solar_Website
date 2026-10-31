<script setup lang="ts">
import type { PublicNewsPayload } from '#shared/contracts/public-news'
import { isPublicNewsPayload } from '#shared/contracts/public-news'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { editorialPublicResponseStatus, editorialPublicRouteRobots } from '#shared/editorial-public-route'
import { publicSeoDescription, publicSeoTitle } from '#shared/public-seo'
import NewsCard from '~/components/news/NewsCard.vue'
import NewsCta from '~/components/news/NewsCta.vue'
import NewsFilters from '~/components/news/NewsFilters.vue'

const props = defineProps<{ slugOverride?: string }>()
const route = useRoute()
const config = useRuntimeConfig()
const slug = computed(() => props.slugOverride || (typeof route.params.slug === 'string' ? route.params.slug : ''))
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data, error } = await useAsyncData<unknown>(
  () => `public-news-category-${slug.value}-${String(route.query.p || '1')}-${String(route.query.q || '')}`,
  () => $fetch('/_migration/news', { query: {
    category: slug.value,
    ...(typeof route.query.p === 'string' ? { p: route.query.p } : {}),
    ...(typeof route.query.q === 'string' ? { q: route.query.q } : {}),
  } }),
  { watch: [slug, () => route.query.p, () => route.query.q] },
)
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const news = computed<PublicNewsPayload | null>(() => {
  const payload = isPublicNewsPayload(data.value) ? data.value : null
  return payload?.category?.slug === slug.value ? payload : null
})
const category = computed(() => news.value?.category || null)
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
    <main class="pts-container pb-16">
      <section v-if="error || !news || !category" class="min-h-[45vh] py-12" :role="responseStatus === 404 ? 'status' : 'alert'">
        <h1 class="mb-3 text-3xl font-black tracking-tight">{{ responseStatus === 404 ? 'Không tìm thấy danh mục bài viết' : 'Tạm thời không thể tải danh mục bài viết' }}</h1>
        <p class="text-slate-500">{{ responseStatus === 404 ? 'Danh mục này hiện không tồn tại hoặc đã ẩn.' : 'Nguồn nội dung hiện không khả dụng.' }}</p>
      </section>

      <template v-else>
        <header class="pt-6 pb-7">
          <h1 class="mb-2.5 text-[clamp(30px,4vw,44px)] font-black leading-[1.15] tracking-tight text-navy-800">{{ category.name }}</h1>
          <p v-if="category.description" class="m-0 leading-relaxed text-slate-500">{{ category.description }}</p>
        </header>

        <div class="mb-6 flex flex-col items-start justify-between gap-3.5 nav:flex-row nav:items-center">
          <NewsFilters :categories="news.categories" :selected="category" :all-label="news.labels.all" :filters-label="news.labels.results" />
          <form class="flex h-11 w-full flex-none items-center rounded-chip border border-gray-200 bg-white nav:w-[250px]" method="get" :action="route.path" role="search">
            <label class="sr-only" for="news-category-search">{{ news.labels.search }}</label>
            <input id="news-category-search" name="q" type="search" class="w-full min-w-0 border-0 px-3 text-base text-navy-800 outline-none focus-visible:ring-2 focus-visible:ring-gold-500" :value="typeof route.query.q === 'string' ? route.query.q : ''" :placeholder="news.labels.search" maxlength="120">
            <button type="submit" class="h-11 w-11 flex-none border-0 bg-transparent text-navy-800" :aria-label="news.labels.search">⌕</button>
          </form>
        </div>

        <section v-if="news.articles.length" class="grid grid-cols-1 gap-x-5 gap-y-6 tb:grid-cols-2 nav:grid-cols-3" :aria-label="news.labels.results">
          <NewsCard v-for="article in news.articles" :key="article.id" :article="article" :read-label="news.labels.read" :read-more-label="news.labels.readMore" />
        </section>
        <div v-else class="min-h-[260px] py-[52px] text-center text-slate-500" role="status">
          <h2 class="mb-3 text-navy-800">{{ news.labels.empty }}</h2>
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
