<script setup lang="ts">
import { resolveEquipmentIcon } from '#shared/catalog-menu-presentation'
import CatalogMenuIcon from '~/components/CatalogMenuIcon.vue'
import type { PublicSearchPayload } from '#shared/contracts/public-search'
import type { PublicCatalogPayload } from '#shared/contracts/public-catalog'
import CatalogProductGrid from '~/components/catalog/CatalogProductGrid.vue'
import CatalogMasthead from '~/components/catalog/CatalogMasthead.vue'
import CatalogFilterSidebar from '~/components/catalog/CatalogFilterSidebar.vue'
import SearchSuggestions from '~/components/search/SearchSuggestions.vue'
import { publicSeoDescription } from '#shared/public-seo'
import { publicPageQueryValue } from '#shared/public-pagination'

/*
 * để dùng chung đúng một logic cho cả preview noindex và route public thật -
 * không nhân bản. Link nội bộ dùng route.path (không hardcode).
 */
const { site } = await usePublicSite()
const { openConsultation } = useStorefrontModal()
const links = usePublicLinks()
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
  suggestOpen.value = false
  pageSuggest.value?.remember(nextKeyword)
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
  'search',
  () => $fetch('/_api/search', { query: searchQuery.value }),
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
const filterDrawerOpen = ref(false)
const hasActiveFilters = computed(() => selectedBrandIds.value.length > 0 || selectedPriceKeys.value.length > 0)
// Search shares the catalog's filter panel, so both pages look and behave the same.
const sidebarFilters = computed(() => ({
  subcategories: [],
  brands: filters.value?.brands ?? [],
  priceRanges: filters.value?.priceRanges ?? [],
}) as NonNullable<PublicCatalogPayload['filters']>)
const sidebarSelected = computed(() => ({
  subcategoryIds: [],
  brandIds: selectedBrandIds.value,
  priceKeys: selectedPriceKeys.value,
  sort: selectedSort.value,
}) as NonNullable<PublicCatalogPayload['selected']>)
function sidebarFilterLink(overrides: { brand?: string; price?: string }) {
  return filterHref({
    brandIds: overrides.brand === undefined ? selectedBrandIds.value : parsePositiveIds(overrides.brand),
    priceKeys: overrides.price === undefined ? selectedPriceKeys.value : parsePriceKeys(overrides.price),
  })
}
const sidebarColumn = ref<HTMLElement | null>(null)
useStickyColumn(sidebarColumn)
const otherTerms = computed(() => popularKeywords.filter((term) => foldText(term) !== foldText(keyword.value)).slice(0, 5))
const activeChips = computed(() => [
  ...selectedBrandIds.value.flatMap((id) => {
    const item = filters.value?.brands.find((option) => option.id === id)
    return item ? [{ key: `brand-${id}`, label: item.name, to: filterHref({ brandIds: selectedBrandIds.value.filter((value) => value !== id), priceKeys: selectedPriceKeys.value }) }] : []
  }),
  ...selectedPriceKeys.value.flatMap((key) => {
    const item = filters.value?.priceRanges.find((option) => option.key === key)
    return item ? [{ key: `price-${key}`, label: item.label, to: filterHref({ brandIds: selectedBrandIds.value, priceKeys: selectedPriceKeys.value.filter((value) => value !== key) }) }] : []
  }),
])
const suggestOpen = ref(false)
const searchCard = ref<HTMLElement | null>(null)
const pageSuggest = ref<InstanceType<typeof SearchSuggestions> | null>(null)
function onCardFocusOut(event: FocusEvent) {
  if (!searchCard.value?.contains(event.relatedTarget as Node | null)) suggestOpen.value = false
}
const termsRail = ref<HTMLElement | null>(null)
useSlidingPill(termsRail, 'search-terms')

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

function onSortChange(value: string) {
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
      :compact="Boolean(keyword)"
    />
    <main class="pts-container pb-16">
      <div>
        <!-- The search card overlaps the hero, so typing again is right where the eye already is. -->
        <div ref="searchCard" class="search-card mb-7 p-4 sm:p-5" :class="keyword ? 'search-card--over' : 'mt-6'" @focusout="onCardFocusOut" @keydown.esc="suggestOpen = false">
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
                autocomplete="off"
                role="combobox"
                aria-autocomplete="list"
                :aria-expanded="suggestOpen"
                :aria-controls="pageSuggest?.listId"
                :aria-activedescendant="pageSuggest?.activeOptionId"
                @focus="suggestOpen = true"
                @input="suggestOpen = true"
                @keydown="pageSuggest?.handleKey($event)"
                class="h-12 w-full rounded-btn border border-slate-300 bg-white pl-10 pr-10 text-base text-navy-900 placeholder:text-slate-400 focus:border-gold-600 focus:outline-none"
              >
              <button
                v-if="keywordDraft"
                type="button"
                aria-label="Xóa từ khóa"
                class="absolute right-2.5 top-1/2 grid size-7 -translate-y-1/2 cursor-pointer place-items-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-navy-900"
                @click="submitKeyword('')"
              >
                <UIcon name="i-lucide-x" class="size-4" aria-hidden="true" />
              </button>
              <div v-if="suggestOpen" class="search-card__drop">
                <SearchSuggestions
                  ref="pageSuggest"
                  :keyword="keywordDraft"
                  :site="site"
                  :search-path="selfPath"
                  id-prefix="pts-page-suggest"
                  @leave="suggestOpen = false"
                  @pick-term="submitKeyword"
                />
              </div>
            </div>
            <button
              type="submit"
              class="inline-flex h-12 shrink-0 cursor-pointer items-center justify-center gap-2 rounded-btn bg-gold-500 px-6 text-sm font-extrabold text-navy-950 transition-colors hover:bg-gold-400"
            >
              <UIcon name="i-lucide-search" class="size-4" aria-hidden="true" />
              <span>Tìm sản phẩm</span>
            </button>
          </form>

          <div class="mt-3.5 flex items-center gap-3 max-tb:flex-col max-tb:items-start max-tb:gap-1.5">
            <span class="flex-none text-[13px] font-bold text-slate-500">Tra cứu nhanh:</span>
            <div ref="termsRail" class="search-terms flex max-w-full gap-2 overflow-x-auto overscroll-x-contain py-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
              <button
                v-for="term in popularKeywords"
                :key="term"
                type="button"
                class="public-filter-chip flex min-h-10 flex-none cursor-pointer items-center whitespace-nowrap px-[13px] text-[14px] font-bold"
                :class="keyword.toLowerCase() === term.toLowerCase() ? 'text-navy-950' : 'text-navy-800'"
                :aria-current="keyword.toLowerCase() === term.toLowerCase() ? 'page' : undefined"
                @click="submitKeyword(term)"
              >
                {{ term }}
              </button>
            </div>
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
        <div v-else-if="data" :class="hasFilters ? 'grid gap-x-[30px] max-[1199px]:gap-x-6 min-[1002px]:grid-cols-[240px_minmax(0,1fr)] min-[1200px]:grid-cols-[280px_minmax(0,1fr)]' : ''">
        <div v-if="hasFilters" class="hidden min-[1002px]:block">
          <div ref="sidebarColumn" class="search-sidebar-sticky">
            <CatalogFilterSidebar
              id-prefix="search-filter-sidebar"
              :categories="[]"
              selected-category=""
              :filters="sidebarFilters"
              :selected="sidebarSelected"
              keyword=""
              :filter-link="sidebarFilterLink"
            />
          </div>
        </div>

        <section class="min-w-0" aria-label="Kết quả tìm kiếm" :aria-busy="pending">
          <div v-if="data.products.length || hasFilters" class="catalog-toolbar-host mb-5 flex flex-wrap items-center justify-between gap-4 max-tb:gap-2 min-[1002px]:empty:hidden" :class="data.products.length ? '' : 'min-[1002px]:hidden'">
            <div v-if="data.products.length" class="flex flex-wrap items-center gap-3 max-tb:w-full">
              <h2 class="m-h-panel m-0 text-[22px] font-extrabold text-navy-950">{{ data.pagination.total }} sản phẩm cho “{{ keyword }}”</h2>
            </div>
            <div class="m-sticky-tools flex items-center gap-2 max-tb:w-full">
            <label v-if="data.products.length" class="flex items-center gap-2 text-sm text-slate-600 max-tb:order-2 max-tb:min-w-0 max-tb:flex-1">
              <span class="whitespace-nowrap max-tb:sr-only">Sắp xếp</span>
              <USelect
                :model-value="selectedSort"
                :items="sortOptions"
                aria-label="Sắp xếp sản phẩm"
                color="neutral"
                variant="none"
                class="w-full min-w-0 min-[769px]:w-[170px] min-[769px]:flex-none"
                :ui="{ base: 'h-11 w-full min-w-0 rounded-btn border border-slate-300 bg-white px-3 text-sm font-normal text-navy-800 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25 m-lg max-tb:rounded-full' }"
                @update:model-value="onSortChange"
              />
            </label>
            <button
              v-if="hasFilters"
              class="public-neutral-button m-lg inline-flex min-h-11 items-center justify-center gap-2 rounded-chip px-4 text-sm font-bold text-navy-800 max-tb:order-1 max-tb:flex-1 min-[1002px]:hidden"
              type="button"
              aria-controls="search-filter-drawer"
              :aria-expanded="filterDrawerOpen"
              @click="filterDrawerOpen = true"
            >
              <UIcon name="i-lucide-sliders-horizontal" class="size-4" aria-hidden="true" />
              Bộ lọc
            </button>
            </div>
          </div>
          <!-- Phones have the filters in a drawer, so what is applied stays visible above the results. -->
          <div v-if="activeChips.length" class="search-applied mb-3 flex gap-2 overflow-x-auto min-[1002px]:hidden" aria-label="Bộ lọc đang áp dụng">
            <NuxtLink v-for="chip in activeChips" :key="chip.key" :to="chip.to" class="search-applied__chip" :aria-label="`Bỏ bộ lọc ${chip.label}`">
              {{ chip.label }}<UIcon name="i-lucide-x" class="size-3.5 flex-none" aria-hidden="true" />
            </NuxtLink>
          </div>

          <CatalogProductGrid v-if="data.products.length" class="search-grid" :class="{ 'is-updating': pending }" :products="data.products" />
          <div v-if="!data.products.length" class="search-empty">
            <span class="search-empty__icon" aria-hidden="true"><UIcon name="i-lucide-search-x" class="size-6" /></span>
            <h2 class="m-0 text-xl font-extrabold text-navy-950">Không tìm thấy sản phẩm phù hợp.</h2>
            <p class="m-0 mt-2 max-w-[56ch] text-[15px] leading-6 text-slate-600">
              {{ hasActiveFilters
                ? 'Bộ lọc đang chọn chưa có sản phẩm khớp với từ khóa này. Hãy bỏ bớt bộ lọc, hoặc gửi yêu cầu để kỹ sư Phúc Thịnh Solar tra cứu mã tương đương trong kho.'
                : 'Thử chọn từ khóa ngắn gọn hơn hoặc gửi yêu cầu để kỹ sư Phúc Thịnh Solar tra cứu mã tương đương trong kho.' }}
            </p>
            <div class="mt-4 flex flex-wrap justify-center gap-2" aria-label="Thử từ khóa khác">
              <button v-for="term in otherTerms" :key="term" type="button" class="public-filter-chip min-h-10 cursor-pointer px-[13px] text-[14px] font-bold text-navy-800" @click="submitKeyword(term)">{{ term }}</button>
            </div>
            <div class="mt-5 flex flex-wrap justify-center gap-3">
              <NuxtLink
                v-if="hasActiveFilters"
                :to="filterHref({ brandIds: [], priceKeys: [] })"
                class="inline-flex min-h-11 items-center rounded-btn bg-gold-500 px-5 text-sm font-extrabold text-navy-950 no-underline hover:bg-gold-400"
              >
                Bỏ bộ lọc
              </NuxtLink>
              <button
                type="button"
                class="inline-flex min-h-11 cursor-pointer items-center gap-1.5 rounded-btn px-5 text-sm font-extrabold text-navy-950"
                :class="hasActiveFilters ? 'public-neutral-button' : 'bg-gold-500 hover:bg-gold-400'"
                @click="openConsultation('Tìm kiếm sản phẩm: ' + keyword)"
              >
                <span>Nhận tư vấn mã tương đương</span>
                <span aria-hidden="true">→</span>
              </button>
              <NuxtLink v-if="!hasActiveFilters" :to="links.generic('/san-pham/')" class="public-neutral-button inline-flex min-h-11 items-center rounded-btn px-5 text-sm font-bold text-navy-800 no-underline">
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

    <UDrawer
      v-if="hasFilters"
      v-model:open="filterDrawerOpen"
      direction="left"
      title="Bộ lọc sản phẩm"
      description="Chọn các tiêu chí để lọc kết quả tìm kiếm."
      :handle="false"
      :close="false"
      :ui="{
        overlay: 'glass-overlay',
        content: '!h-dvh !max-h-none !w-[min(340px,92vw)] !max-w-none !rounded-none overflow-hidden border-0 bg-white p-0 text-navy-800 shadow-2xl ring-0 m-sheet-glass',
      }"
    >
      <template #content>
        <div id="search-filter-drawer" class="flex h-full min-h-0 w-full flex-col">
          <div class="flex min-h-[62px] flex-none items-center justify-between border-b border-slate-200 px-4">
            <h2 class="m-0 text-base font-black text-navy-800">Bộ lọc sản phẩm</h2>
            <button type="button" class="public-neutral-button inline-grid h-11 w-11 place-items-center rounded-btn p-0 text-navy-800" aria-label="Đóng bộ lọc" @click="filterDrawerOpen = false">
              <UIcon name="i-lucide-x" class="size-5" aria-hidden="true" />
            </button>
          </div>
          <div class="min-h-0 flex-1 overflow-y-auto" data-lenis-prevent>
            <CatalogFilterSidebar
              id-prefix="search-filter-drawer"
              :categories="[]"
              selected-category=""
              :filters="sidebarFilters"
              :selected="sidebarSelected"
              keyword=""
              :filter-link="sidebarFilterLink"
              hide-title
              drawer
            />
          </div>
          <div class="flex-none border-t border-slate-200 p-3">
            <button type="button" class="m-gold-cta min-h-12 w-full rounded-btn bg-[image:var(--pts-gold-gradient)] text-[15px] font-extrabold text-navy-950" @click="filterDrawerOpen = false">Xem kết quả</button>
          </div>
        </div>
      </template>
    </UDrawer>
  </div>
</template>

<style scoped>
.search-card--over { margin-top: -32px; }
.search-card { position: relative; z-index: 5; border: 1px solid rgb(255 255 255 / 80%); border-radius: var(--radius-md); background: rgb(255 255 255 / 94%); box-shadow: 0 18px 44px -22px rgb(6 21 55 / 38%); -webkit-backdrop-filter: blur(14px) saturate(140%); backdrop-filter: blur(14px) saturate(140%); }
.search-card__drop { position: absolute; top: calc(100% + 8px); right: 0; left: 0; z-index: 30; padding: 12px; border: 1px solid rgb(10 34 81 / 10%); border-radius: var(--radius-md); background: #fff; box-shadow: 0 24px 60px -24px rgb(6 21 55 / 42%); }
.search-card input:focus-visible { outline: 2px solid var(--color-gold-400); outline-offset: 0; }
.search-grid { transition: opacity .18s ease; }
.search-grid.is-updating { opacity: .55; }
.search-applied { scrollbar-width: none; }
.search-applied::-webkit-scrollbar { display: none; }
.search-applied__chip { display: inline-flex; min-height: 40px; flex: none; align-items: center; gap: 6px; padding: 0 12px; border: 1px solid var(--color-gold-300); border-radius: var(--radius-btn); background: var(--color-gold-50); color: var(--color-navy-950); font-size: 13.5px; font-weight: 700; text-decoration: none; }
.search-empty { display: flex; flex-direction: column; align-items: center; padding: clamp(28px, 4vw, 48px) 24px; border: 1px solid rgb(10 34 81 / 10%); border-radius: var(--radius-md); background: linear-gradient(180deg, #fff 0%, #f6f8fb 100%); text-align: center; }
.search-empty__icon { display: grid; width: 52px; height: 52px; margin-bottom: 14px; place-items: center; border-radius: var(--radius-sm); background: rgb(228 168 73 / 14%); color: var(--color-gold-700); }
@media (min-width: 1002px) {
  .search-sidebar-sticky { position: sticky; top: 20px; }
}
@media (max-width: 820px) {
  .search-terms { width: calc(100% + 16px); margin-right: -16px; padding-right: 16px; }
}
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
