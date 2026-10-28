<script setup lang="ts">
import type { PublicSearchPayload } from '#shared/contracts/public-search'
import CatalogProductGrid from '~/components/catalog/CatalogProductGrid.vue'

/*
 * Tách từ migration-search-preview.vue (xem đó + pages/tim-kiem/index.vue)
 * để dùng chung đúng một logic cho cả preview noindex và route public thật -
 * không nhân bản. Link nội bộ dùng route.path (không hardcode).
 */
const { site } = await usePublicSite()
const route = useRoute()
const selfPath = computed(() => route.path)
const keyword = computed(() => typeof route.query.keyword === 'string' ? route.query.keyword.trim() : '')
const page = computed(() => typeof route.query.page === 'string' ? route.query.page : '')
const searchQuery = computed(() => ({ keyword: keyword.value, ...(page.value ? { page: page.value } : {}) }))

const { data, error, pending } = await useAsyncData<PublicSearchPayload>(
  () => 'migration-search-' + JSON.stringify(searchQuery.value),
  () => $fetch('/_migration/search', { query: searchQuery.value }),
  { watch: [searchQuery] },
)

const totalPages = computed(() => data.value ? Math.max(1, Math.ceil(data.value.pagination.total / data.value.pagination.perPage)) : 1)
const currentPage = computed(() => data.value?.pagination.page ?? 1)
function pageHref(value: number) {
  return { path: selfPath.value, query: { keyword: keyword.value, page: String(value) } }
}

useSeoMeta({
  title: computed(() => keyword.value ? 'Tìm kiếm: ' + keyword.value + ' — Phúc Thịnh Solar' : 'Tìm kiếm — Phúc Thịnh Solar'),
})
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <SiteHeader :site="site" />
    <main class="mx-auto w-full max-w-(--container-page) px-(--spacing-gutter) py-10 max-ph:py-7">
      <SiteBreadcrumb :items="[{ label: 'Tìm kiếm' }]" />
      <h1 class="mb-2 text-[clamp(30px,5vw,48px)] font-black tracking-tight">Kết quả tìm kiếm</h1>
      <p v-if="keyword" class="mb-8 text-slate-500">Từ khóa: <strong class="text-navy-800">{{ keyword }}</strong><span v-if="data"> · {{ data.pagination.total }} kết quả</span></p>

      <div v-if="!keyword" class="rounded-btn border border-gray-200 p-6 text-slate-500">Nhập từ khóa trong ô tìm kiếm ở header.</div>
      <div v-else-if="pending" class="py-16 text-slate-500" role="status">Đang tìm sản phẩm…</div>
      <div v-else-if="error" class="rounded-btn border border-red-200 bg-red-50 p-6 text-red-800" role="alert">Không thể tải kết quả tìm kiếm từ CMS local.</div>
      <template v-else-if="data">
        <CatalogProductGrid :products="data.products" />
        <p v-if="!data.products.length" class="rounded-btn border border-gray-200 p-8 text-center text-slate-500">Không tìm thấy sản phẩm phù hợp.</p>

        <nav v-if="totalPages > 1" class="mt-10 flex items-center gap-2" aria-label="Phân trang tìm kiếm">
          <NuxtLink v-if="currentPage > 1" :to="pageHref(currentPage - 1)" class="rounded-chip border border-gray-200 px-4 py-2 font-bold no-underline">Trước</NuxtLink>
          <span class="px-2 text-sm text-slate-500">Trang {{ currentPage }} / {{ totalPages }}</span>
          <NuxtLink v-if="currentPage < totalPages" :to="pageHref(currentPage + 1)" class="rounded-chip border border-gray-200 px-4 py-2 font-bold no-underline">Sau</NuxtLink>
        </nav>
      </template>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>
