<script setup lang="ts">
import type { PublicSearchPayload } from '#shared/contracts/public-search'
import CatalogProductGrid from '~/components/catalog/CatalogProductGrid.vue'
import CatalogMasthead from '~/components/catalog/CatalogMasthead.vue'
import SearchFilterOptions from '~/components/catalog/SearchFilterOptions.vue'
import { publicSeoDescription } from '#shared/public-seo'
import { publicPageQueryValue } from '#shared/public-pagination'

/*
 * Tách từ migration-search-preview.vue (xem đó + pages/tim-kiem/index.vue)
 * để dùng chung đúng một logic cho cả preview noindex và route public thật -
 * không nhân bản. Link nội bộ dùng route.path (không hardcode).
 */
const { site } = await usePublicSite()
const route = useRoute()
const selfPath = computed(() => route.path)
const keyword = computed(() => typeof route.query.keyword === 'string' ? route.query.keyword.trim() : '')
const page = computed(() => publicPageQueryValue(route.query.page, route.query.p))
const brand = computed(() => typeof route.query.brand === 'string' ? route.query.brand.trim() : '')
const price = computed(() => typeof route.query.price === 'string' ? route.query.price.trim() : '')
const sort = computed(() => typeof route.query.sort === 'string' ? route.query.sort : '')
const searchQuery = computed(() => ({
  keyword: keyword.value,
  ...(page.value ? { page: page.value } : {}),
  ...(brand.value ? { brand: brand.value } : {}),
  ...(price.value ? { price: price.value } : {}),
  ...(sort.value ? { sort: sort.value } : {}),
}))

const { data, error, pending } = await useAsyncData<PublicSearchPayload>(
  () => 'migration-search-' + JSON.stringify(searchQuery.value),
  () => $fetch('/_migration/search', { query: searchQuery.value }),
  { watch: [searchQuery] },
)
const publicSearchEnabled = computed(() => route.path === '/tim-kiem/')
// PHP's search.php only runs a query when ?keyword is present; a bare visit
// renders its "enter a keyword" prompt at 200. Mirror that: only a keyword
// that actually failed against the CMS is a backend error (502).
if (import.meta.server && publicSearchEnabled.value && keyword.value && (error.value || !data.value)) {
  setResponseStatus(502, 'Bad Gateway')
}

const totalPages = computed(() => data.value ? Math.max(1, Math.ceil(data.value.pagination.total / data.value.pagination.perPage)) : 1)
const currentPage = computed(() => data.value?.pagination.page ?? 1)
const filters = computed(() => data.value?.filters)
const selectedBrandIds = computed(() => data.value?.selected?.brandIds ?? parsePositiveIds(brand.value))
const selectedPriceKeys = computed(() => data.value?.selected?.priceKeys ?? parsePriceKeys(price.value))
const selectedSort = computed(() => data.value?.selected?.sort ?? (sort.value || 'default'))
const sortOptions = [
  { value: 'default', label: 'Mặc định' },
  { value: 'abc', label: 'Tên A → Z' },
  { value: 'abc-desc', label: 'Tên Z → A' },
  { value: 'new', label: 'Mới nhất' },
  { value: 'new-desc', label: 'Cũ nhất' },
  { value: 'price', label: 'Giá tăng dần' },
  { value: 'price-desc', label: 'Giá giảm dần' },
]
const hasFilters = computed(() => Boolean(filters.value && (filters.value.brands.length || filters.value.priceRanges.length)))
const mobileFiltersOpen = ref(false)

function parsePositiveIds(value: string): number[] {
  if (!/^[1-9][0-9]*(?:,[1-9][0-9]*)*$/.test(value)) return []
  return [...new Set(value.split(',').map(Number).filter(Number.isSafeInteger))]
}

function parsePriceKeys(value: string): string[] {
  if (!/^[123](?:,[123])*$/.test(value)) return []
  return [...new Set(value.split(','))]
}

function filterHref(selection: { brandIds: number[]; priceKeys: string[] }) {
  const query: Record<string, string> = { keyword: keyword.value }
  if (selection.brandIds.length) query.brand = selection.brandIds.join(',')
  if (selection.priceKeys.length) query.price = selection.priceKeys.join(',')
  if (sort.value) query.sort = sort.value
  return { path: selfPath.value, query }
}

function onSortChange(event: Event) {
  const value = (event.target as HTMLSelectElement).value
  navigateTo({ path: selfPath.value, query: { keyword: keyword.value, ...(brand.value ? { brand: brand.value } : {}), ...(price.value ? { price: price.value } : {}), ...(value !== 'default' ? { sort: value } : {}) } })
}

function pageHref(value: number) {
  return {
    path: selfPath.value,
    query: {
      keyword: keyword.value,
      page: String(value),
      ...(brand.value ? { brand: brand.value } : {}),
      ...(price.value ? { price: price.value } : {}),
      ...(sort.value ? { sort: sort.value } : {}),
    },
  }
}

useSeoMeta({
  title: 'Tìm kiếm | Phúc Thịnh Solar',
  robots: 'noindex, nofollow, noarchive',
})
usePublicSocialMeta({
  enabled: computed(() => publicSearchEnabled.value && !!data.value && !error.value),
  title: 'Tìm kiếm | Phúc Thịnh Solar',
  description: computed(() => publicSeoDescription('', site.value.brand.name)),
  canonicalPath: '/tim-kiem/',
})
</script>

<template>
  <div class="min-h-screen bg-white text-navy-800">
    <SiteHeader :site="site" />
    <div class="border-b border-slate-200/70 bg-slate-50/80">
      <div class="pts-container">
        <SiteBreadcrumb :items="[{ label: 'Tìm kiếm' }]" />
      </div>
    </div>
    <CatalogMasthead
      heading="Sản phẩm năng lượng mặt trời"
      description="Giải pháp năng lượng phù hợp cho gia đình, doanh nghiệp và công trình"
      :hero="null"
    />
    <main class="pts-container pb-16">
      <div class="pt-6">
        <div v-if="!keyword" class="rounded-btn border border-gray-200 p-6 text-slate-500">Nhập từ khóa trong ô tìm kiếm ở header.</div>
        <div v-else-if="pending" class="py-16 text-slate-500" role="status">Đang tìm sản phẩm…</div>
        <div v-else-if="error" class="rounded-btn border border-red-200 bg-red-50 p-6 text-red-800" role="alert">Không thể tải kết quả tìm kiếm từ CMS local.</div>
        <div v-else-if="data" :class="hasFilters ? 'grid gap-8 nav:grid-cols-[260px_minmax(0,1fr)]' : ''">
        <aside v-if="hasFilters && filters" class="hidden nav:block">
          <SearchFilterOptions
            :filters="filters"
            :selected-brand-ids="selectedBrandIds"
            :selected-price-keys="selectedPriceKeys"
            :filter-link="filterHref"
          />
        </aside>

        <section class="min-w-0" aria-label="Kết quả tìm kiếm">
          <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
            <h2 class="m-0 text-[22px] font-extrabold text-navy-950">Danh sách sản phẩm</h2>
            <label class="flex items-center gap-2 text-sm text-slate-600">
              Sắp xếp
              <select :value="selectedSort" class="h-10 border border-gray-300 bg-white px-3 text-navy-800" @change="onSortChange">
                <option v-for="option in sortOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </label>
          </div>
          <button
            v-if="hasFilters && filters"
            class="mb-5 inline-flex min-h-11 items-center gap-2 rounded-chip border border-gray-200 px-4 font-bold nav:hidden"
            type="button"
            aria-controls="search-filter-panel"
            :aria-expanded="mobileFiltersOpen"
            @click="mobileFiltersOpen = !mobileFiltersOpen"
          >
            Bộ lọc
          </button>
          <div
            v-if="hasFilters && filters"
            id="search-filter-panel"
            :class="mobileFiltersOpen ? 'mb-6 nav:hidden' : 'hidden nav:hidden'"
          >
            <SearchFilterOptions
              :filters="filters"
              :selected-brand-ids="selectedBrandIds"
              :selected-price-keys="selectedPriceKeys"
              :filter-link="filterHref"
            />
          </div>

          <CatalogProductGrid :products="data.products" />
          <p v-if="!data.products.length" class="rounded-btn border border-gray-200 p-8 text-center text-slate-500">Không tìm thấy sản phẩm phù hợp.</p>

          <nav v-if="totalPages > 1" class="mt-10 flex flex-wrap items-center justify-center gap-2" aria-label="Phân trang tìm kiếm">
            <SitePaginationButton v-if="currentPage > 1" :to="pageHref(currentPage - 1)">Trước</SitePaginationButton>
            <span class="px-2 text-sm text-slate-500">Trang {{ currentPage }} / {{ totalPages }}</span>
            <SitePaginationButton v-if="currentPage < totalPages" :to="pageHref(currentPage + 1)">Sau</SitePaginationButton>
          </nav>
        </section>
      </div>
      </div>
    </main>
    <SiteFooter :site="site" />
  </div>
</template>
