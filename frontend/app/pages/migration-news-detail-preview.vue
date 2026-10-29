<script setup lang="ts">
import { isPublicNewsDetailPayload } from '#shared/contracts/public-news-detail'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { editorialPublicResponseStatus, editorialPublicRouteRobots } from '#shared/editorial-public-route'
import NewsCta from '~/components/news/NewsCta.vue'
import { canonicalPublicUrl } from '#shared/public-seo'

const route = useRoute()
const config = useRuntimeConfig()
const isBarePublicRoute = computed(() => typeof route.params.slug === 'string' && route.path === `/${route.params.slug}/`)
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : (typeof route.query.slug === 'string' ? route.query.slug : ''))
const { data, error } = await useAsyncData('migration-news-detail', () => $fetch('/_migration/news-detail', { query: { slug: slug.value } }), { watch: [slug] })
const payload = computed(() => isPublicNewsDetailPayload(data.value) ? data.value : null)
const errorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
const responseStatus = editorialPublicResponseStatus(isBarePublicRoute.value && config.public.newsDetailPublicRouteEnabled, errorStatus, !payload.value)
if (import.meta.server && responseStatus) setResponseStatus(responseStatus, responseStatus === 404 ? 'Not Found' : 'Bad Gateway')
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const links = useMigrationLinks()

useSeoMeta({
  title: () => payload.value?.article.seo.title || (isBarePublicRoute.value ? 'Kiến thức' : 'Kiến thức — internal preview'),
  description: () => payload.value?.article.seo.description || '',
  robots: computed(() => editorialPublicRouteRobots(isBarePublicRoute.value && config.public.newsDetailPublicRouteEnabled, config.public.newsDetailPublicRouteIndexable, !!payload.value && !error.value)),
})
usePublicSocialMeta({
  enabled: computed(() => isBarePublicRoute.value && config.public.newsDetailPublicRouteEnabled && !!payload.value && !error.value),
  title: computed(() => payload.value?.article.seo.title || 'Kiến thức'),
  description: computed(() => payload.value?.article.seo.description || ''),
  canonicalPath: computed(() => payload.value?.article.canonical || route.path),
  image: computed(() => payload.value?.article.cover),
  type: 'article',
})
const articleJsonLd = computed(() => {
  const article = payload.value?.article
  if (!article) return null
  const url = canonicalPublicUrl(config.public.canonicalSiteOrigin, article.canonical)
  if (!url) return null
  const image = article.cover ? canonicalPublicUrl(config.public.canonicalSiteOrigin, article.cover) : null
  const node: Record<string, unknown> = {
    '@context': 'https://schema.org',
    '@type': 'NewsArticle',
    mainEntityOfPage: { '@type': 'WebPage', '@id': url },
    headline: article.title,
    url,
    author: { '@type': 'Person', name: article.author.name },
    publisher: { '@type': 'Organization', name: 'Phúc Thịnh Solar' },
  }
  if (image) node.image = [image]
  if (article.datePublished) node.datePublished = article.datePublished
  if (article.dateModified) node.dateModified = article.dateModified
  if (article.seo.description || article.description) node.description = article.seo.description || article.description
  return node
})
useHead({
  script: computed(() => articleJsonLd.value ? [{
    type: 'application/ld+json',
    innerHTML: JSON.stringify(articleJsonLd.value).replace(/</g, '\\u003c'),
  }] : []),
})
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <main class="mx-auto w-full max-w-[1200px] px-4 py-8 nav:py-16">
      <SiteBreadcrumb :items="[{ label: 'Kiến thức', to: links.generic('/tin-tuc/') }, ...(payload?.article.category && 'name' in payload.article.category ? [{ label: payload.article.category.name }] : []), ...(payload ? [{ label: payload.article.title }] : [])]" />

      <div v-if="error || !payload" class="min-h-[45vh] py-16">
        <h1 class="mb-3 text-3xl font-black tracking-tight">{{ isBarePublicRoute && responseStatus === 404 ? 'Không tìm thấy bài viết' : (isBarePublicRoute ? 'Không thể tải bài viết' : 'Preview bài viết') }}</h1>
        <p class="mb-4 text-slate-500">{{ isBarePublicRoute ? 'Nội dung bài viết hiện không khả dụng.' : 'Không thể tải bài viết CMS thật với slug này. Preview không hiển thị dữ liệu giả.' }}</p>
        <a class="font-bold text-gold-700" :href="links.generic('/tin-tuc/')">Quay lại Kiến thức →</a>
      </div>

      <template v-else>
        <NewsDetailArticle :article="payload.article" />
        <NewsDetailRelated :items="payload.related" />
        <NewsCta v-if="payload.cta" :cta="payload.cta" :phone-label="payload.cta.hotline?.label || 'Gọi ngay hotline'" />
      </template>
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
