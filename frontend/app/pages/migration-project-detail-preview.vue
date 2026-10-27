<script setup lang="ts">
import type { PublicSitePayload } from '#shared/contracts/public-site'
import type { PublicProjectDetailPayload } from '#shared/contracts/public-project-detail'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { isPublicProjectDetailPayload } from '#shared/contracts/public-project-detail'
import ProjectDetailPage from '~/components/project-detail/ProjectDetailPage.vue'

const route = useRoute()
const slug = computed(() => typeof route.query.slug === 'string' ? route.query.slug : '')
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data, error } = await useFetch<unknown>('/_migration/project-detail', { query: computed(() => ({ slug: slug.value })), default: () => null })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const payload = computed<PublicProjectDetailPayload | null>(() => isPublicProjectDetailPayload(data.value) ? data.value : null)

useSeoMeta({ title: computed(() => payload.value ? `${payload.value.project.title} — preview` : 'Project detail preview'), robots: 'noindex, nofollow, noarchive' })
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
            { label: 'Dự án đã thực hiện', to: '/migration-projects-preview' },
            ...(payload?.project.category ? [{ label: payload.project.category.name }] : []),
            ...(payload ? [{ label: payload.project.title }] : []),
          ]"
        />
      </div>

      <div v-if="error || !payload" class="mx-auto min-h-[45vh] w-full max-w-[1766px] px-4 py-12" role="status">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Project detail preview</h1>
        <p class="text-slate-500">Không thể tải dự án CMS hoặc slug chưa hợp lệ. Preview không hiển thị dữ liệu dự án giả.</p>
      </div>

      <ProjectDetailPage v-else :project="payload.project" />
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
