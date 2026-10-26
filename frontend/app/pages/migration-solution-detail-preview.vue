<script setup lang="ts">
import type { PublicSolutionDetailPayload } from '../../shared/contracts/public-solution-detail'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import SolutionDetailPage from '~/components/solution-detail/SolutionDetailPage.vue'

useSeoMeta({ title: 'Giải pháp — internal preview', robots: 'noindex, nofollow, noarchive' })
const slug = String(useRoute().query.slug || '')
const { data, error } = await useAsyncData<PublicSolutionDetailPayload>(`migration-solution-detail-${slug}`, () => $fetch('/_migration/solution-detail', { query: { slug } }))

/**
 * PHÁT HIỆN: trang này trước đó KHÔNG gọi SiteHeader/SiteFooter/SiteBreadcrumb
 * — cùng lỗi đã sửa ở trang danh sách Giải pháp. Ảnh production có đủ header
 * và breadcrumb 3 cấp "Trang chủ > Giải pháp > <tên giải pháp>".
 */
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <main>
      <div class="mx-auto w-full max-w-[1766px] px-4">
        <SiteBreadcrumb :items="[{ label: 'Giải pháp', to: '/migration-solutions-preview' }, ...(data ? [{ label: data.solution.title }] : [])]" />
      </div>

      <div v-if="error || !data" class="mx-auto min-h-[45vh] w-full max-w-[1766px] px-4 py-12">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Giải pháp — internal preview</h1>
        <p class="text-slate-500">Không thể tải dữ liệu CMS trong môi trường hiện tại. Preview không hiển thị nội dung giả.</p>
      </div>

      <SolutionDetailPage v-else :data="data" />
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
