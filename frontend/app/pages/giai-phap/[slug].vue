<script setup lang="ts">
import type { PublicSolutionDetailPayload } from '#shared/contracts/public-solution-detail'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import SolutionDetailPage from '~/components/solution-detail/SolutionDetailPage.vue'

const route = useRoute()
const slug = computed(() => typeof route.params.slug === 'string' ? route.params.slug : '')
const { data, error } = await useAsyncData<PublicSolutionDetailPayload>(() => `public-solution-detail-${slug.value}`, () => $fetch('/_migration/solution-detail', { query: { slug: slug.value } }), { watch: [slug] })

/**
 * PHÁT HIỆN: trang này trước đó KHÔNG gọi SiteHeader/SiteFooter/SiteBreadcrumb
 * — cùng lỗi đã sửa ở trang danh sách Giải pháp. Ảnh production có đủ header
 * và breadcrumb 3 cấp "Trang chủ > Giải pháp > <tên giải pháp>".
 */
const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const requestUrl = useRequestURL()

if (error.value && error.value.statusCode === 404) setResponseStatus(404)
useSeoMeta({
  title: computed(() => data.value ? `${data.value.solution.title} — Giải pháp Phúc Thịnh Solar` : 'Giải pháp năng lượng mặt trời — Phúc Thịnh Solar'),
  description: computed(() => data.value?.solution.summary || ''),
  robots: computed(() => data.value && !error.value ? 'index, follow' : 'noindex, nofollow, noarchive'),
})
useHead(() => ({
  link: data.value ? [{ rel: 'canonical', href: new URL(data.value.path, requestUrl.origin).toString() }] : [],
}))
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <main>
      <div class="mx-auto w-full max-w-[1766px] px-4">
        <SiteBreadcrumb :items="[{ label: 'Giải pháp', to: '/giai-phap/' }, ...(data ? [{ label: data.solution.title }] : [])]" />
      </div>

      <div v-if="error || !data" class="mx-auto min-h-[45vh] w-full max-w-[1766px] px-4 py-12">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Không tìm thấy giải pháp</h1>
        <p class="text-slate-500">Giải pháp có thể đã được chuyển hoặc không còn được hiển thị.</p>
      </div>

      <SolutionDetailPage v-else :data="data" />
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
