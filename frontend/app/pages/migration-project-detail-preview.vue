<script setup lang="ts">
import type { PublicSitePayload } from '#shared/contracts/public-site'
import type { PublicProjectDetailPayload } from '#shared/contracts/public-project-detail'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { isPublicProjectDetailPayload } from '#shared/contracts/public-project-detail'
import { editorialPublicResponseStatus, editorialPublicRouteRobots } from '#shared/editorial-public-route'
import ProjectDetailPage from '~/components/project-detail/ProjectDetailPage.vue'

const route = useRoute()
const config = useRuntimeConfig()
const isBarePublicRoute = computed(() => typeof route.params.slug === 'string' && route.path === `/${route.params.slug}/`)
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : (typeof route.query.slug === 'string' ? route.query.slug : ''))
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data, error } = await useFetch<unknown>('/_migration/project-detail', { query: computed(() => ({ slug: slug.value })), default: () => null })
const payload = computed<PublicProjectDetailPayload | null>(() => isPublicProjectDetailPayload(data.value) ? data.value : null)
const errorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
const responseStatus = editorialPublicResponseStatus(isBarePublicRoute.value && config.public.projectDetailPublicRouteEnabled, errorStatus, !payload.value)
if (import.meta.server && responseStatus) setResponseStatus(responseStatus, responseStatus === 404 ? 'Not Found' : 'Bad Gateway')
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)

useSeoMeta({
  title: computed(() => payload.value ? (isBarePublicRoute.value ? payload.value.seo.title : `${payload.value.seo.title} — preview`) : (isBarePublicRoute.value ? 'Dự án' : 'Project detail preview')),
  description: computed(() => payload.value?.seo.description ?? ''),
  robots: computed(() => editorialPublicRouteRobots(isBarePublicRoute.value && config.public.projectDetailPublicRouteEnabled, config.public.projectDetailPublicRouteIndexable, !!payload.value && !error.value)),
})
usePublicSocialMeta({
  enabled: computed(() => isBarePublicRoute.value && config.public.projectDetailPublicRouteEnabled && !!payload.value && !error.value),
  title: computed(() => payload.value?.seo.title || payload.value?.project.title || 'Dự án'),
  description: computed(() => payload.value?.seo.description || ''),
  canonicalPath: computed(() => payload.value?.project.url || route.path),
  image: computed(() => payload.value?.project.coverImage),
  type: 'article',
})
const links = useMigrationLinks()
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <main>
      <!--
        Breadcrumb trên ảnh production có thêm cấp danh mục ("Dự án đã thực
        hiện > Cá nhân > ..."), nằm trên nền trắng, TÁCH khỏi dải xám full-bleed
        của ProjectDetailPage (đo pixel xác nhận dải xám kéo hết x=0..1920,
        không chung khối với breadcrumb).
      -->
      <div class="mx-auto w-full max-w-[1766px] px-4">
        <SiteBreadcrumb
          :items="[
            { label: 'Dự án đã thực hiện', to: links.generic('/du-an-da-thuc-hien/') },
            ...(payload?.project.category ? [{ label: payload.project.category.name }] : []),
            ...(payload ? [{ label: payload.project.title }] : []),
          ]"
        />
      </div>

      <div v-if="error || !payload" class="mx-auto min-h-[45vh] w-full max-w-[1766px] px-4 py-12" role="status">
        <h1 class="mb-3 text-3xl font-black tracking-tight">{{ isBarePublicRoute && responseStatus === 404 ? 'Không tìm thấy dự án' : (isBarePublicRoute ? 'Không thể tải dự án' : 'Project detail preview') }}</h1>
        <p class="text-slate-500">{{ isBarePublicRoute ? 'Nội dung dự án hiện không khả dụng.' : 'Không thể tải dự án CMS hoặc slug chưa hợp lệ. Preview không hiển thị dữ liệu dự án giả.' }}</p>
      </div>

      <ProjectDetailPage v-else :project="payload.project" :content="payload.content" />
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
