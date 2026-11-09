<script setup lang="ts">
import type { DraftPreviewQuery } from '#shared/draft-preview'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import type { PublicProjectDetailPayload } from '#shared/contracts/public-project-detail'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { isPublicProjectDetailPayload } from '#shared/contracts/public-project-detail'
import { editorialPublicResponseStatus, editorialPublicRouteRobots } from '#shared/editorial-public-route'
import ProjectDetailPage from '~/components/project-detail/ProjectDetailPage.vue'

const route = useRoute()
const config = useRuntimeConfig()
const props = defineProps<{ preview?: DraftPreviewQuery }>()
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : '')
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_api/site', { default: () => fallbackSite })
const { data, error } = await useFetch<unknown>(props.preview ? '/_api/post-draft-preview' : '/_api/project-detail', { query: computed(() => props.preview ?? { slug: slug.value }), default: () => null })
const payload = computed<PublicProjectDetailPayload | null>(() => isPublicProjectDetailPayload(data.value) ? data.value : null)
const errorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
const responseStatus = editorialPublicResponseStatus(true, errorStatus, !payload.value)
if (import.meta.server && responseStatus) setResponseStatus(responseStatus, responseStatus === 404 ? 'Not Found' : 'Bad Gateway')
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const pageKeywords = computed(() => payload.value?.seo.keywords || '')

useSeoMeta({
  title: computed(() => payload.value ? payload.value.seo.title : 'Dự án'),
  description: computed(() => payload.value?.seo.description ?? ''),
  robots: computed(() => props.preview ? 'noindex, nofollow, noarchive' : editorialPublicRouteRobots(true, config.public.projectDetailPublicRouteIndexable, !!payload.value && !error.value)),
})
useHead({ meta: computed(() => pageKeywords.value ? [{ name: 'keywords', content: pageKeywords.value }] : []) })
usePublicSocialMeta({
  enabled: computed(() => !!payload.value && !error.value),
  title: computed(() => payload.value?.seo.title || payload.value?.project.title || 'Dự án'),
  description: computed(() => payload.value?.seo.description || ''),
  canonicalPath: computed(() => payload.value?.canonical.url || route.path),
  image: computed(() => payload.value?.project.coverImage),
  type: 'article',
})
const links = usePublicLinks()
</script>

<template>
  <div class="text-navy-800">

    <main>
      <div class="border-b border-slate-200/70 bg-slate-50/80">
        <div class="pts-container">
          <SiteBreadcrumb
            :items="[
              { label: 'Dự án đã thực hiện', to: links.generic('/du-an-da-thuc-hien/') },
              ...(payload?.project.category ? [{ label: payload.project.category.name, to: links.generic(payload.project.category.url) }] : []),
              ...(payload ? [{ label: payload.project.title }] : []),
            ]"
          />
        </div>
      </div>

      <div v-if="error || !payload" class="pts-container min-h-[45vh] py-12" role="status">
        <h1 class="mb-3 text-3xl font-black tracking-tight">{{ responseStatus === 404 ? 'Không tìm thấy dự án' : 'Không thể tải dự án' }}</h1>
        <p class="text-slate-500">{{ 'Nội dung dự án hiện không khả dụng.' }}</p>
      </div>

      <ProjectDetailPage v-else :project="payload.project" :content="payload.content" />
    </main>

  </div>
</template>
