<script setup lang="ts">
import type { PublicNewsPayload } from '#shared/contracts/public-news'
import { isPublicNewsPayload } from '#shared/contracts/public-news'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import NewsCard from '~/components/news/NewsCard.vue'
import NewsCta from '~/components/news/NewsCta.vue'
import NewsFeatured from '~/components/news/NewsFeatured.vue'

const route = useRoute()
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const requestQuery = computed(() => {
  const query: Record<string, string> = {}
  for (const key of ['q', 'p', 'category', 'category_type', 'category_path']) if (typeof route.query[key] === 'string' && route.query[key]) query[key] = route.query[key] as string
  return query
})
const { data, error } = await useAsyncData<unknown>('public-news', () => $fetch('/_migration/news', { query: requestQuery.value }), { watch: [requestQuery] })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const news = computed(() => isPublicNewsPayload(data.value) ? data.value : null)
const requestUrl = useRequestURL()
const pageUrl = (page: number) => {
  const query = new URLSearchParams(requestQuery.value)
  if (page <= 1) query.delete('p'); else query.set('p', String(page))
  return `/tin-tuc/${query.toString() ? `?${query}` : ''}`
}
const categoryUrl = (slug?: string) => {
  const query = new URLSearchParams()
  if (requestQuery.value.q) query.set('q', requestQuery.value.q)
  if (slug) return `/${slug}/${query.toString() ? `?${query}` : ''}`
  const suffix = query.toString()
  return `/tin-tuc/${suffix ? `?${suffix}` : ''}`
}
const pageNumbers = computed(() => {
  if (!news.value) return []
  const total = Math.ceil(news.value.pagination.total / news.value.pagination.perPage)
  const current = news.value.pagination.page
  const result: (number | '…')[] = []
  for (let page = 1; page <= total; page++) if (page === 1 || page === total || Math.abs(page - current) <= 2) { if (result.length && typeof result[result.length - 1] === 'number' && page > (result[result.length - 1] as number) + 1) result.push('…'); result.push(page) }
  return result
})
useSeoMeta({
  title: () => `${news.value?.title || 'Kiến thức'} — Phúc Thịnh Solar`,
  description: () => news.value?.description || '',
  robots: computed(() => news.value && !error.value ? 'index, follow' : 'noindex, nofollow, noarchive'),
})
useHead(() => ({ link: [{ rel: 'canonical', href: new URL('/tin-tuc/', requestUrl.origin).toString() }] }))
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <main class="pb-16">
      <div class="mx-auto w-full max-w-[1766px] px-4">
        <SiteBreadcrumb :items="[{ label: news?.category?.name || 'Kiến thức' }]" />

        <div v-if="error || !news" class="min-h-[45vh] py-12" role="status">
            <h1 class="mb-3 text-3xl font-black tracking-tight">Kiến thức</h1>
          <p class="text-slate-500">Không thể tải nội dung kiến thức. Vui lòng thử lại sau.</p>
        </div>

        <template v-else>
          <!--
            PHÁT HIỆN: CSS preview cũ canh giữa (text-align:center) cho tiêu đề
            trang. Ảnh production canh TRÁI, không giới hạn max-width 760px —
            tiêu đề kéo dài theo chiều rộng container như mọi trang khác.
          -->
          <header class="pt-6 pb-7">
            <h1 class="mb-2.5 text-[clamp(30px,4vw,44px)] font-black leading-[1.15] tracking-tight text-navy-800">{{ news.title }}</h1>
            <p v-if="news.description" class="m-0 leading-relaxed text-slate-500">{{ news.description }}</p>
          </header>

          <NewsFeatured :articles="news.featured" :label="news.labels.featured" :read-label="news.labels.read" :read-more-label="news.labels.readMore" />

          <div class="mb-6 flex flex-col items-start justify-between gap-3.5 nav:flex-row nav:items-center">
            <nav class="flex w-full max-w-full min-w-0 gap-[7px] overflow-x-auto px-0.5 pb-2 pt-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" :aria-label="news.labels.results">
              <a class="flex min-h-[42px] flex-none items-center whitespace-nowrap rounded-chip px-[15px] text-[13px] font-bold no-underline" :class="!news.category ? 'bg-navy-800 text-white' : 'bg-slate-50 text-navy-800 hover:bg-gray-200'" :href="categoryUrl()">{{ news.labels.all }}</a>
              <a v-for="category in news.categories" :key="category.id" class="flex min-h-[42px] flex-none items-center gap-2 whitespace-nowrap rounded-chip px-[15px] text-[13px] font-bold no-underline" :class="news.category?.id === category.id ? 'bg-navy-800 text-white' : 'bg-slate-50 text-navy-800 hover:bg-gray-200'" :href="categoryUrl(category.slug)">
                <img v-if="category.image" :src="category.image" alt="" class="h-[22px] w-[22px] object-contain" width="22" height="22">{{ category.name }}
              </a>
            </nav>

            <form class="flex h-11 w-full flex-none items-center rounded-chip border border-gray-200 bg-white nav:w-[250px]" method="get" action="/tin-tuc/" role="search">
              <label class="sr-only" for="news-search">{{ news.labels.search }}</label>
              <input id="news-search" name="q" type="search" class="w-full min-w-0 border-0 px-3 text-[13px] text-navy-800 outline-none focus-visible:ring-2 focus-visible:ring-gold-500" :value="requestQuery.q || ''" :placeholder="news.labels.search" maxlength="120">
              <button type="submit" class="h-[42px] w-[42px] flex-none border-0 bg-transparent text-navy-800" :aria-label="news.labels.search">⌕</button>
              <input v-if="requestQuery.category" type="hidden" name="category" :value="requestQuery.category">
            </form>
          </div>

          <p v-if="requestQuery.q" class="-mt-2 mb-[22px] text-slate-500">
            {{ news.labels.results }}: <strong>{{ news.pagination.total }}</strong> — "{{ requestQuery.q }}"
            <a class="text-gold-700" href="/tin-tuc/">× {{ news.labels.reset }}</a>
          </p>

          <section v-if="news.articles.length" class="grid grid-cols-1 gap-x-5 gap-y-6 tb:grid-cols-2 nav:grid-cols-3" :aria-label="news.labels.results">
            <NewsCard v-for="article in news.articles" :key="article.id" :article="article" :read-label="news.labels.read" :read-more-label="news.labels.readMore" />
          </section>
          <div v-else class="min-h-[260px] py-[52px] text-center text-slate-500" role="status">
            <h2 class="mb-3 text-navy-800">{{ news.labels.empty }}</h2>
            <a class="font-bold text-gold-700" href="/tin-tuc/">{{ news.labels.reset }} →</a>
          </div>

          <nav v-if="news.pagination.total > news.pagination.perPage" class="mt-7 flex flex-wrap justify-center gap-2" :aria-label="news.labels.pages">
            <a v-if="news.pagination.page > 1" class="grid h-[38px] min-w-[38px] place-items-center rounded-full border border-gray-200 text-navy-800 no-underline hover:border-gold-500 hover:bg-gold-400" rel="prev" :href="pageUrl(news.pagination.page - 1)">‹</a>
            <template v-for="(page, index) in pageNumbers" :key="`${page}-${index}`">
              <span v-if="page === '…'" class="grid h-[38px] min-w-[38px] place-items-center text-navy-800">…</span>
              <a
                v-else
                class="grid h-[38px] min-w-[38px] place-items-center rounded-full border text-navy-800 no-underline"
                :class="page === news.pagination.page ? 'border-gold-500 bg-gold-400' : 'border-gray-200 hover:border-gold-500 hover:bg-gold-400'"
                :aria-current="page === news.pagination.page ? 'page' : undefined"
                :href="pageUrl(page)"
              >
                {{ page }}
              </a>
            </template>
            <a v-if="news.pagination.page < Math.ceil(news.pagination.total / news.pagination.perPage)" class="grid h-[38px] min-w-[38px] place-items-center rounded-full border border-gray-200 text-navy-800 no-underline hover:border-gold-500 hover:bg-gold-400" rel="next" :href="pageUrl(news.pagination.page + 1)">›</a>
          </nav>

          <NewsCta v-if="news.cta" :cta="news.cta" :phone-label="news.labels.ctaPhone" />
        </template>
      </div>
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
