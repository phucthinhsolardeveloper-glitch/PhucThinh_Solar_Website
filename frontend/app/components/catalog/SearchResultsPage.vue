<script setup lang="ts">
import { resolveEquipmentIcon } from '#shared/catalog-menu-presentation'
import CatalogMenuIcon from '~/components/CatalogMenuIcon.vue'
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
const { openConsultation } = useStorefrontModal()
const links = useMigrationLinks()
const route = useRoute()
const selfPath = computed(() => route.path)
const keyword = computed(() => typeof route.query.keyword === 'string' ? route.query.keyword.trim() : '')
const keywordDraft = ref(keyword.value)
watch(keyword, (val) => { keywordDraft.value = val })

const popularKeywords = [
  'Đèn đường',
  'Blue Carbon',
  'LONGi',
  'SolaX',
  'Pin lưu trữ',
  'Jindian',
  'Bơm nước',
]

function submitKeyword(nextKeyword = keywordDraft.value) {
  const cleaned = nextKeyword.trim()
  keywordDraft.value = cleaned
  navigateTo({
    path: selfPath.value,
    query: cleaned ? { keyword: cleaned } : {},
  })
}
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
  'migration-search',
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
    <div class="border-b border-slate-200/70 bg-slate-50/80">
      <div class="pts-container">
        <SiteBreadcrumb :items="[{ label: 'Tìm kiếm' }]" />
      </div>
    </div>
    <CatalogMasthead
      :heading="keyword ? `Kết quả tìm kiếm: “${keyword}”` : 'Tìm kiếm sản phẩm'"
      :description="keyword ? 'Giải pháp năng lượng phù hợp cho gia đình, doanh nghiệp và công trình' : 'Tìm theo tên thiết bị, công suất, mã sản phẩm hoặc thương hiệu.'"
      :hero="null"
      :benefits="site.catalogBenefits ?? []"
    />
    <main class="pts-container pb-16">
      <div class="pt-6">
        <!-- Thanh tìm kiếm trực tiếp và từ khóa gợi ý 1 chạm -->
        <div class="mb-7 pts-note p-4 sm:p-5">
          <form class="flex flex-col gap-3 sm:flex-row sm:items-center" role="search" @submit.prevent="submitKeyword()">
            <div class="relative flex-1">
              <UIcon name="i-lucide-search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
              <input
                v-model="keywordDraft"
                type="search"
                name="keyword"
                maxlength="120"
                placeholder="Nhập tên thiết bị, công suất (VD: 500W, 6kW), mã sản phẩm hoặc thương hiệu..."
                aria-label="Từ khóa tìm kiếm sản phẩm"
                class="h-12 w-full rounded-btn border border-slate-300 bg-white pl-10 pr-10 text-base text-navy-900 placeholder:text-slate-400 focus:border-gold-600 focus:outline-none"
              >
              <button
                v-if="keywordDraft"
                type="button"
                aria-label="Xóa từ khóa"
                class="absolute right-2.5 top-1/2 grid size-7 -translate-y-1/2 cursor-pointer place-items-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-navy-900"
                @click="submitKeyword('')"
              >
                ×
              </button>
            </div>
            <button
              type="submit"
              class="inline-flex h-12 shrink-0 cursor-pointer items-center justify-center gap-2 rounded-btn bg-gold-500 px-6 text-sm font-extrabold text-navy-950 transition-colors hover:bg-gold-400"
            >
              <UIcon name="i-lucide-search" class="size-4" aria-hidden="true" />
              <span>Tìm sản phẩm</span>
            </button>
          </form>

          <div class="mt-3.5 flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-slate-500">Tra cứu nhanh:</span>
            <button
              v-for="term in popularKeywords"
              :key="term"
              type="button"
              class="inline-flex min-h-8 cursor-pointer items-center rounded-chip border px-2.5 py-1 text-xs font-bold transition-colors"
              :class="keyword.toLowerCase() === term.toLowerCase()
                ? 'border-navy-900 bg-navy-900 text-gold-300'
                : 'border-slate-200 bg-white text-navy-800 hover:border-gold-500 hover:text-gold-700'"
              @click="submitKeyword(term)"
            >
              {{ term }}
            </button>
          </div>
        </div>

        <div v-if="!keyword" class="rounded-btn border border-gray-200 p-6 text-slate-600 sm:p-8">
          <h2 class="m-0 text-xl font-extrabold text-navy-900">{{ site.productCategories.length ? 'Khám phá theo danh mục' : 'Tìm sản phẩm phù hợp' }}</h2>
          <p class="m-0 mt-1 text-sm text-slate-500">{{ site.productCategories.length ? 'Chọn nhóm thiết bị để xem các sản phẩm đang được công bố.' : 'Chọn một từ khóa gợi ý phía trên để bắt đầu tra cứu.' }}</p>
          <ul v-if="site.productCategories.length" class="search-category-grid">
            <li v-for="cat in site.productCategories" :key="cat.slug">
              <NuxtLink :to="links.generic(cat.url)" class="search-category group pts-panel-sheen">
                <span class="search-category__icon" aria-hidden="true">
                  <CatalogMenuIcon class="size-6" :name="resolveEquipmentIcon(cat.name, cat.url)" />
                </span>
                <span class="search-category__name">{{ cat.name }}</span>
                <UIcon name="i-lucide-arrow-up-right" class="search-category__go size-5" aria-hidden="true" />
              </NuxtLink>
            </li>
          </ul>
        </div>
        <div v-else-if="pending && !data" class="py-16 text-slate-500" role="status">Đang tìm sản phẩm…</div>
        <div v-else-if="error" class="rounded-btn border border-red-200 bg-red-50 p-6 text-red-800" role="alert">Không thể tải kết quả tìm kiếm lúc này. Vui lòng thử lại.</div>
        <div v-else-if="data" :class="hasFilters ? 'grid gap-8 nav:grid-cols-[260px_minmax(0,1fr)]' : ''">
        <aside v-if="hasFilters && filters" class="hidden nav:block">
          <SearchFilterOptions
            :filters="filters"
            :selected-brand-ids="selectedBrandIds"
            :selected-price-keys="selectedPriceKeys"
            :filter-link="filterHref"
          />
        </aside>

        <section class="min-w-0" aria-label="Kết quả tìm kiếm" :aria-busy="pending">
          <div class="catalog-toolbar-host mb-5 flex flex-wrap items-center justify-between gap-4 max-tb:gap-2">
            <div class="flex flex-wrap items-center gap-3 max-tb:w-full">
              <h2 class="m-h-panel m-0 text-[22px] font-extrabold text-navy-950">Danh sách sản phẩm</h2>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600 max-tb:order-2 max-tb:min-w-0 max-tb:flex-1">
              <span class="max-tb:sr-only">Sắp xếp</span>
              <select :value="selectedSort" class="m-lg h-10 max-tb:h-11 max-tb:w-full rounded-chip border border-gray-300 bg-white px-3 text-navy-800" @change="onSortChange">
                <option v-for="option in sortOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </label>
            <button
              v-if="hasFilters && filters"
              class="public-neutral-button m-lg inline-flex min-h-11 items-center justify-center gap-2 rounded-chip px-4 text-sm font-bold max-tb:order-1 max-tb:flex-1 nav:hidden"
              type="button"
              aria-controls="search-filter-panel"
              :aria-expanded="mobileFiltersOpen"
              @click="mobileFiltersOpen = !mobileFiltersOpen"
            >
              Bộ lọc
            </button>
          </div>
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
          <div v-if="!data.products.length" class="rounded-btn border border-gray-200 p-8 text-center text-slate-600">
            <p class="m-0 text-base font-bold text-navy-900">Không tìm thấy sản phẩm phù hợp.</p>
            <p class="m-0 mt-1 text-sm text-slate-500">Thử chọn từ khóa ngắn gọn hơn hoặc gửi yêu cầu để kỹ sư Phúc Thịnh Solar tra cứu mã tương đương trong kho.</p>
            <div class="mt-4 flex flex-wrap justify-center gap-3">
              <button
                type="button"
                class="inline-flex min-h-10 cursor-pointer items-center gap-1.5 rounded-btn bg-gold-500 px-4 py-2 text-xs font-extrabold text-navy-950 hover:bg-gold-400"
                @click="openConsultation('Tìm kiếm sản phẩm: ' + keyword)"
              >
                <span>Nhận tư vấn mã tương đương</span>
                <span aria-hidden="true">→</span>
              </button>
              <NuxtLink :to="links.generic('/san-pham/')" class="inline-flex min-h-10 items-center rounded-btn border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-navy-800 no-underline hover:border-navy-800">
                Xem toàn bộ danh mục
              </NuxtLink>
            </div>
          </div>

          <nav v-if="totalPages > 1" class="mt-10 flex flex-wrap items-center justify-center gap-2" aria-label="Phân trang tìm kiếm">
            <SitePaginationButton v-if="currentPage > 1" :to="pageHref(currentPage - 1)" aria-label="Trang trước">
              <UIcon name="i-lucide-chevron-left" class="size-4" aria-hidden="true" />
            </SitePaginationButton>
            <span class="px-2 text-sm text-slate-500">Trang {{ currentPage }} / {{ totalPages }}</span>
            <SitePaginationButton v-if="currentPage < totalPages" :to="pageHref(currentPage + 1)" aria-label="Trang sau">
              <UIcon name="i-lucide-chevron-right" class="size-4" aria-hidden="true" />
            </SitePaginationButton>
          </nav>
        </section>
      </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
.search-category-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 290px), 1fr)); gap: 12px; margin: 22px 0 0; padding: 0; list-style: none; }
.search-category-grid > li { display: flex; }
.search-category { display: flex; flex: 1; min-height: 76px; align-items: center; gap: 14px; overflow: hidden; padding: 14px 16px; border: 1px solid rgb(10 34 81 / 10%); border-radius: var(--radius-md); background: #fff; color: var(--color-navy-950); font-weight: 800; text-decoration: none; transition: border-color var(--pts-dur-fast) var(--pts-ease), box-shadow var(--pts-dur) var(--pts-ease), transform var(--pts-dur) var(--pts-ease); }
.search-category:hover { border-color: rgb(228 168 73 / 55%); box-shadow: var(--pts-shadow-lift); transform: translateY(-2px); }
.search-category:focus-visible { outline: 3px solid var(--color-gold-500); outline-offset: 3px; }
.search-category__icon { display: grid; width: 46px; height: 46px; flex: none; place-items: center; border-radius: var(--radius-sm); background: rgb(228 168 73 / 14%); color: var(--color-gold-700); transition: background-color var(--pts-dur) var(--pts-ease), color var(--pts-dur) var(--pts-ease); }
.search-category:hover .search-category__icon { background: var(--pts-gold-gradient); color: var(--color-navy-950); }
.search-category__name { min-width: 0; flex: 1; font-size: 15px; line-height: 1.35; }
.search-category__go { flex: none; color: #94a3b8; transition: transform var(--pts-dur) var(--pts-ease), color var(--pts-dur-fast) ease; }
.search-category:hover .search-category__go { color: var(--color-gold-700); transform: translate(2px, -2px); }
@media (prefers-reduced-motion: reduce) { .search-category, .search-category__go { transition: none; } .search-category:hover, .search-category:hover .search-category__go { transform: none; } }
</style>
