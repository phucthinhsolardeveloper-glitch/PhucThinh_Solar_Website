<script setup lang="ts">
import { isPublicNewsDetailPayload } from '#shared/contracts/public-news-detail'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'

const route = useRoute()
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const slug = computed(() => typeof route.query.slug === 'string' ? route.query.slug : '')
const { data, error } = await useAsyncData('migration-news-detail', () => $fetch('/_migration/news-detail', { query: { slug: slug.value } }), { watch: [slug] })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const payload = computed(() => isPublicNewsDetailPayload(data.value) ? data.value : null)
const links = useMigrationLinks()

useSeoMeta({ title: () => payload.value?.article.seo.title || 'Kiến thức — internal preview', description: () => payload.value?.article.seo.description || '', robots: 'noindex, nofollow, noarchive' })
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <main class="mx-auto w-full max-w-[1766px] px-4 py-8 nav:py-16">
      <SiteBreadcrumb :items="[{ label: 'Kiến thức' }, ...(payload?.article.category && 'name' in payload.article.category ? [{ label: payload.article.category.name }] : [])]" />

      <div v-if="error || !payload" class="min-h-[45vh] py-16">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Preview bài viết</h1>
        <p class="mb-4 text-slate-500">Không thể tải bài viết CMS thật với slug này. Preview không hiển thị dữ liệu giả.</p>
        <a class="font-bold text-gold-700" :href="links.generic('/tin-tuc/')">Quay lại Kiến thức →</a>
      </div>

      <template v-else>
        <div class="grid grid-cols-1 gap-8 tb:grid-cols-[minmax(0,2fr)_minmax(240px,1fr)] tb:gap-12">
          <NewsDetailArticle :article="payload.article" />
          <NewsDetailRelated :items="payload.related" />
        </div>
      </template>
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
