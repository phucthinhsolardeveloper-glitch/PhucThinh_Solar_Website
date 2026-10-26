<script setup lang="ts">
import type { PublicNewsPayload } from '#shared/contracts/public-news'
import { isPublicNewsPayload } from '#shared/contracts/public-news'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import NewsCard from '~/components/news/NewsCard.vue'
import NewsCta from '~/components/news/NewsCta.vue'
import NewsFeatured from '~/components/news/NewsFeatured.vue'
import NewsFilters from '~/components/news/NewsFilters.vue'

const route = useRoute()
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const requestQuery = computed(() => {
  const query: Record<string, string> = {}
  for (const key of ['q', 'p', 'category']) if (typeof route.query[key] === 'string' && route.query[key]) query[key] = route.query[key] as string
  return query
})
const { data, error } = await useAsyncData<unknown>('migration-news', () => $fetch('/_migration/news', { query: requestQuery.value }), { watch: [requestQuery] })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const news = computed(() => isPublicNewsPayload(data.value) ? data.value : null)
const pageUrl = (page: number) => {
  const query = new URLSearchParams(requestQuery.value)
  if (page <= 1) query.delete('p'); else query.set('p', String(page))
  return `/migration-news-preview/${query.toString() ? `?${query}` : ''}`
}
const pageNumbers = computed(() => {
  if (!news.value) return []
  const total = Math.ceil(news.value.pagination.total / news.value.pagination.perPage)
  const current = news.value.pagination.page
  const result: (number | '…')[] = []
  for (let page = 1; page <= total; page++) if (page === 1 || page === total || Math.abs(page - current) <= 2) { if (result.length && typeof result[result.length - 1] === 'number' && page > (result[result.length - 1] as number) + 1) result.push('…'); result.push(page) }
  return result
})
useSeoMeta({ title: () => `${news.value?.title || 'Kiến thức'} — internal preview`, description: () => news.value?.description || '', robots: 'noindex, nofollow, noarchive' })
</script>

<template>
  <div class="news-preview"><SiteHeader :site="site" /><main class="news-preview__main"><div class="pts-container"><SiteBreadcrumb :items="[{ label: news?.category?.name || 'Kiến thức' }]" /><div v-if="error || !news" class="news-preview__fallback" role="status"><h1>Kiến thức — internal preview</h1><p>Không thể tải dữ liệu CMS trong môi trường hiện tại. Preview không hiển thị bài viết giả.</p></div><template v-else><header class="news-preview__heading"><h1>{{ news.title }}</h1><p v-if="news.description">{{ news.description }}</p></header><NewsFeatured :articles="news.featured" :label="news.labels.featured" :read-label="news.labels.read" :read-more-label="news.labels.readMore" /><div class="news-toolbar"><NewsFilters :categories="news.categories" :selected="news.category" :all-label="news.labels.all" :filters-label="news.labels.results" :query="requestQuery.q" /><form class="news-search" method="get" action="/migration-news-preview/" role="search"><label class="sr-only" for="news-search">{{ news.labels.search }}</label><input id="news-search" name="q" type="search" :value="requestQuery.q || ''" :placeholder="news.labels.search" maxlength="120"><button type="submit" :aria-label="news.labels.search">⌕</button><input v-if="requestQuery.category" type="hidden" name="category" :value="requestQuery.category"></form></div><p v-if="requestQuery.q" class="news-query">{{ news.labels.results }}: <strong>{{ news.pagination.total }}</strong> — “{{ requestQuery.q }}” <a href="/migration-news-preview/">× {{ news.labels.reset }}</a></p><section v-if="news.articles.length" class="news-grid" :aria-label="news.labels.results"><NewsCard v-for="article in news.articles" :key="article.id" :article="article" :read-label="news.labels.read" :read-more-label="news.labels.readMore" /></section><div v-else class="news-empty" role="status"><h2>{{ news.labels.empty }}</h2><a href="/migration-news-preview/">{{ news.labels.reset }} →</a></div><nav v-if="news.pagination.total > news.pagination.perPage" class="news-pagination" :aria-label="news.labels.pages"><a v-if="news.pagination.page > 1" rel="prev" :href="pageUrl(news.pagination.page - 1)">‹</a><template v-for="(page, index) in pageNumbers" :key="`${page}-${index}`"><span v-if="page === '…'">…</span><a v-else :class="{ 'is-active': page === news.pagination.page }" :aria-current="page === news.pagination.page ? 'page' : undefined" :href="pageUrl(page)">{{ page }}</a></template><a v-if="news.pagination.page < Math.ceil(news.pagination.total / news.pagination.perPage)" rel="next" :href="pageUrl(news.pagination.page + 1)">›</a></nav><NewsCta v-if="news.cta" :cta="news.cta" :phone-label="news.labels.ctaPhone" /></template></div></main><SiteFooter :site="site" /></div>
</template>

<style>
@import '~/assets/css/news-preview.css';
.sr-only { position: absolute; width: 1px; height: 1px; padding: 0; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
</style>
