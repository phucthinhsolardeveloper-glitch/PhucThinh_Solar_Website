<script setup lang="ts">
import type { PublicCatalogPayload } from '#shared/contracts/public-catalog'
import type { PublicSitePayload, PublicProductCategoryNode } from '#shared/contracts/public-site'
import type { CatalogMenuIconName } from '#shared/catalog-menu-presentation'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import CatalogFilterSidebar from '~/components/catalog/CatalogFilterSidebar.vue'
import CatalogProductGrid from '~/components/catalog/CatalogProductGrid.vue'
import CatalogMasthead from '~/components/catalog/CatalogMasthead.vue'
import CatalogMenuIcon from '~/components/CatalogMenuIcon.vue'
import SiteBreadcrumb from '~/components/SiteBreadcrumb.vue'
import SiteHeader from '~/components/SiteHeader.vue'
import SiteFooter from '~/components/SiteFooter.vue'
import { productCategorySegmentsFromPublicPath } from '#shared/public-route-gate'
import { isPublicSeoIndexable, publicSeoRobots } from '#shared/public-seo'

/*
 * Tách từ migration-catalog-preview.vue (xem đó + pages/san-pham/index.vue)
 * để dùng chung đúng một logic cho cả preview noindex và route public thật -
 * không nhân bản. Link nội bộ dùng route.path (không hardcode) nên component
 * này tự khớp đúng URL của bất kỳ trang nào render nó.
 */
const props = defineProps<{ categoryPath?: string }>()
const route = useRoute()
const config = useRuntimeConfig()
const links = useMigrationLinks()
const filterDrawerOpen = ref(false)
const categoryNavRef = ref<HTMLElement | null>(null)
const selfPath = computed(() => route.path)
const category = computed(() => props.categoryPath ?? (productCategorySegmentsFromPublicPath(route.path).join('/') || (typeof route.query.category === 'string' ? route.query.category : '')))
const page = computed(() => typeof route.query.page === 'string' ? route.query.page : '')
const perPage = computed(() => typeof route.query.per_page === 'string' ? route.query.per_page : '')
const brand = computed(() => typeof route.query.brand === 'string' ? route.query.brand : '')
const price = computed(() => typeof route.query.price === 'string' ? route.query.price : '')
const subcategory = computed(() => typeof route.query.subcategory === 'string' ? route.query.subcategory : '')
const sort = computed(() => typeof route.query.sort === 'string' ? route.query.sort : '')
const keyword = computed(() => typeof route.query.keyword === 'string' ? route.query.keyword : '')
const need = computed(() => typeof route.query.need === 'string' ? route.query.need : '')
const query = computed(() => ({
  ...(category.value ? { category: category.value } : {}),
  ...(page.value ? { page: page.value } : {}),
  ...(perPage.value ? { per_page: perPage.value } : {}),
  ...(brand.value ? { brand: brand.value } : {}),
  ...(price.value ? { price: price.value } : {}),
  ...(subcategory.value ? { subcategory: subcategory.value } : {}),
  ...(sort.value ? { sort: sort.value } : {}),
  ...(keyword.value ? { keyword: keyword.value } : {}),
  ...(need.value ? { need: need.value } : {}),
}))

/*
 * Doi filter/sort luon ve trang 1 - giu dung quy uoc pagination hien co
 * (pageLink giu nguyen cac query khac), tranh "trang 3 nhung loc ra 0 ket
 * qua" khi khach doi bo loc o trang sau.
 */
function filterLink(overrides: { subcategory?: string; brand?: string; price?: string; sort?: string; need?: string; keyword?: string }) {
  const next: Record<string, string> = { ...query.value, ...overrides }
  for (const key of Object.keys(next)) if (next[key] === '') delete next[key]
  delete next.page
  return { path: selfPath.value, query: next }
}
function onSortChange(event: Event) {
  const value = (event.target as HTMLSelectElement).value
  navigateTo(filterLink({ sort: value === 'default' ? '' : value }))
}
function openFilterDrawer() {
  filterDrawerOpen.value = true
}
function closeFilterDrawer() {
  filterDrawerOpen.value = false
}
function closeFilterDrawerAfterNavigation(event: MouseEvent) {
  if (event.target instanceof Element && event.target.closest('a[href]')) closeFilterDrawer()
}
const { data: siteData, error: siteError } = await useAsyncData<PublicSitePayload>('migration-catalog-site', async () => {
  const payload = await $fetch<unknown>('/_migration/site')
  if (!isPublicSitePayload(payload)) throw createError({ statusCode: 502, statusMessage: 'Invalid PHP site data contract' })
  return payload
})
const { data: catalogData, error: catalogError, pending: catalogPending } = await useAsyncData<PublicCatalogPayload>(
  () => `migration-catalog-${JSON.stringify(query.value)}`,
  () => $fetch('/_migration/catalog', { query: query.value }),
  { watch: [query] },
)

function findCategory(nodes: PublicProductCategoryNode[], chain: string): PublicProductCategoryNode | null {
  for (const node of nodes) {
    if (node.url === `/danh-muc-san-pham/${chain}/`) return node
    const child = findCategory(node.children, chain)
    if (child) return child
  }
  return null
}

const selectedCategory = computed(() => findCategory(siteData.value?.productCategoryTree ?? [], category.value))
if (import.meta.server && !links.active.value && props.categoryPath && siteData.value && !selectedCategory.value) {
  setResponseStatus(404, 'Not Found')
}
const isPublicCatalogPath = computed(() => route.path === '/san-pham/' || route.path === '/thuong-hieu/' || route.path.startsWith('/danh-muc-san-pham/'))
const publicEnabled = computed(() => isPublicCatalogPath.value)
const catalogErrorStatus = (catalogError.value as { statusCode?: unknown } | null)?.statusCode
if (import.meta.server && publicEnabled.value && (siteError.value || catalogError.value || !catalogData.value)) {
  const status = catalogErrorStatus === 404 ? 404 : 502
  setResponseStatus(status, status === 404 ? 'Not Found' : 'Bad Gateway')
}
const activeCategoryRoot = computed(() => {
  const tree = siteData.value?.productCategoryTree ?? []
  if (!selectedCategory.value) return null
  return tree.find((node) => node.url === selectedCategory.value?.url)
    ?? tree.find((node) => node.children.some((child) => child.url === selectedCategory.value?.url))
})
const lightingRailOrder: Record<string, number> = {
  'den-pha-nang-luong-mat-troi': 10,
  'den-pha': 10,
  'den-duong-nang-luong-mat-troi': 20,
  'den-duong': 20,
  'den-san-vuon': 30,
  'den-ufo': 40,
  'den-op-tran': 50,
}
const categoryNavItems = computed(() => {
  const items = selectedCategory.value
    ? activeCategoryRoot.value?.children ?? []
    : (siteData.value?.productCategoryTree ?? []).filter((node) => node.slug !== 'den-san-vuon')

  // The PHP catalog applies this explicit lighting rail order after the Admin taxonomy query.
  if (activeCategoryRoot.value?.slug !== 'den-nlmt') return items
  return [...items].sort((a, b) => (lightingRailOrder[a.slug] ?? 1000) - (lightingRailOrder[b.slug] ?? 1000))
})
const categoryAllHref = computed(() => links.generic(activeCategoryRoot.value?.url ?? '/san-pham/'))
const sidebarCategories = computed(() => selectedCategory.value
  ? []
  : (siteData.value?.productCategoryTree ?? []).map((node) => ({ ...node, children: [] })))
const subcategoryTitle = computed(() => activeCategoryRoot.value?.slug === 'den-nlmt' ? 'Loại đèn' : 'Loại sản phẩm')
function categoryIcon(slug: string): CatalogMenuIconName {
  if (slug.includes('den-pha')) return 'flood'
  if (slug.includes('den-duong')) return 'street'
  if (slug.includes('den-san-vuon')) return 'garden'
  if (slug.includes('den-ufo')) return 'ufo'
  if (slug.includes('den-op-tran')) return 'ceiling'
  if (slug.includes('den')) return 'light'
  if (slug.includes('tam-pin')) return 'panel'
  if (slug.includes('bien-tan') || slug.includes('inverter')) return 'inverter'
  if (slug.includes('pin-luu-tru')) return 'battery'
  if (slug.includes('bom')) return 'pump'
  if (slug.includes('quat')) return 'fan'
  return 'cog'
}
function categoryNavLabel(category: PublicProductCategoryNode): string {
  return category.slug === 'den-san-vuon' ? 'Đèn Sân Vườn' : category.name
}

function revealActiveCategory() {
  const nav = categoryNavRef.value
  const activeItem = nav?.querySelector<HTMLElement>('[aria-current="page"]')
  if (!nav || !activeItem || typeof window === 'undefined') return

  const navRect = nav.getBoundingClientRect()
  const itemRect = activeItem.getBoundingClientRect()
  const left = nav.scrollLeft + itemRect.left - navRect.left - (nav.clientWidth - itemRect.width) / 2
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  nav.scrollTo({ left: Math.max(0, left), behavior: reducedMotion ? 'auto' : 'smooth' })
}

onMounted(() => revealActiveCategory())
watch(() => selectedCategory.value?.url, async () => {
  await nextTick()
  requestAnimationFrame(revealActiveCategory)
}, { flush: 'post' })

/**
 * Nếu lưới vẫn giữ nguyên hai cột khi không có danh mục/bộ lọc thì nội dung
 * rơi vào cột sidebar và trang co lại thành một dải hẹp. Chỉ tạo cột trái khi
 * panel có dữ liệu thực.
 */
const hasFilters = computed(() => {
  const filters = catalogData.value?.filters
  return !!filters && (filters.subcategories.length > 0 || filters.brands.length > 0 || filters.priceRanges.length > 0 || (filters.needs?.length ?? 0) > 0)
})
const hasSidebar = computed(() => (siteData.value?.productCategoryTree?.length ?? 0) > 0 || hasFilters.value)
const emptyFilters: NonNullable<PublicCatalogPayload['filters']> = { brands: [], priceRanges: [], subcategories: [], needs: [] }
const emptySelected: NonNullable<PublicCatalogPayload['selected']> = { brandIds: [], priceKeys: [], subcategoryIds: [], sort: 'default', needKeys: [] }
/*
 * Ẩn hai lựa chọn sắp xếp theo giá khi public_product_prices đang tắt - PHP
 * (ptsCatalogSortOrderBy) cũng im lặng bỏ qua yêu cầu sort theo giá ẩn,
 * nhưng vẫn hiện lựa chọn trong dropdown sẽ gây hiểu nhầm là sắp xếp có tác
 * dụng trong khi thực tế không đổi thứ tự.
 */
const sortOptions = computed(() => {
  const base = [
    { value: 'default', label: 'Mặc định' },
    { value: 'abc', label: 'Tên A-Z' },
    { value: 'abc-desc', label: 'Tên Z-A' },
    { value: 'price', label: 'Giá tăng dần' },
    { value: 'price-desc', label: 'Giá giảm dần' },
    { value: 'new', label: 'Mới nhất' },
    { value: 'new-desc', label: 'Cũ nhất' },
  ]
  if (catalogData.value?.parity.prices.included) return base
  return base.filter((option) => option.value !== 'price' && option.value !== 'price-desc')
})
const heading = computed(() => catalogData.value?.category && !Array.isArray(catalogData.value.category) ? catalogData.value.category.name : 'Sản phẩm năng lượng mặt trời')
const catalogBreadcrumbItems = computed(() => {
  const root = route.path.startsWith('/thuong-hieu/')
    ? { label: 'Thương hiệu', to: '/thuong-hieu/' }
    : { label: 'Sản phẩm', to: '/san-pham/' }
  return category.value ? [root, { label: heading.value }] : [root]
})
const metadataTitle = computed(() => catalogData.value?.seo.title || [heading.value, 'Phúc Thịnh Solar'].join(' — '))
const metadataDescription = computed(() => catalogData.value?.seo.description || 'Khám phá thiết bị chiếu sáng, tấm pin, biến tần và giải pháp năng lượng mặt trời tại Phúc Thịnh Solar.')
const metadataKeywords = computed(() => catalogData.value?.seo.keywords ?? '')
const metadataCanonicalPath = computed(() => route.path === '/thuong-hieu/' ? route.path : (catalogData.value?.canonical.url || route.path))
const catalogFilterKeys = new Set(['category', 'page', 'per_page', 'brand', 'price', 'subcategory', 'sort', 'keyword', 'need'])
const hasQueryFilters = computed(() => Object.keys(route.query).some((key) => catalogFilterKeys.has(key)))
const catalogIndexable = computed(() => {
  const enabled = route.path.startsWith('/danh-muc-san-pham/')
    ? isPublicSeoIndexable(config.public.catalogCategoryPublicRouteIndexable, true)
    : isPublicSeoIndexable(config.public.catalogPublicRouteIndexable, true)
  return publicEnabled.value && enabled && !!catalogData.value && !catalogError.value && !siteError.value
    && !hasQueryFilters.value && (!props.categoryPath || !!selectedCategory.value)
})
useSeoMeta({
  title: metadataTitle,
  description: metadataDescription,
  robots: computed(() => publicSeoRobots(catalogIndexable.value, !!catalogData.value && !catalogError.value && !siteError.value, hasQueryFilters.value)),
})
useHead({
  meta: computed(() => metadataKeywords.value ? [{ name: 'keywords', content: metadataKeywords.value }] : []),
})
usePublicSocialMeta({
  enabled: computed(() => publicEnabled.value && !!catalogData.value && !catalogError.value && !siteError.value),
  title: metadataTitle,
  description: metadataDescription,
  canonicalPath: metadataCanonicalPath,
  image: computed(() => catalogData.value?.seo.image || catalogData.value?.categoryHero?.image || undefined),
})
const bannerDescription = computed(() => {
  const path = category.value
  if (!path) return 'Giải pháp năng lượng phù hợp cho gia đình, doanh nghiệp và công trình'
  if (path.includes('den-duong')) return 'Chiếu sáng ổn định cho đường phố, lối đi và khu vực công cộng'
  if (path.includes('den-pha')) return 'Chiếu sáng mạnh mẽ cho sân bãi, nhà xưởng và công trình'
  if (path.includes('den-san-vuon')) return 'Tạo điểm nhấn ánh sáng cho sân vườn, lối đi và khuôn viên'
  if (path.includes('den-op-tran')) return 'Chiếu sáng tiện nghi cho hiên nhà, hành lang và không gian sinh hoạt'
  if (path.includes('den-')) return 'Lựa chọn chiếu sáng phù hợp cho đường phố, sân nhà và khuôn viên'
  if (path.includes('tam-pin')) return 'Tạo nguồn điện sạch, ổn định cho gia đình và công trình'
  if (path.includes('pin-luu-tru')) return 'Lưu trữ năng lượng an toàn, chủ động cho mọi nhu cầu sử dụng'
  if (path.includes('quat-')) return 'Giải pháp làm mát tiết kiệm điện cho gia đình và không gian mở'
  if (path.includes('bien-tan')) return 'Chuyển đổi điện năng hiệu quả, vận hành ổn định và bền bỉ'
  return 'Giải pháp năng lượng phù hợp cho gia đình, doanh nghiệp và công trình'
})
const totalPages = computed(() => catalogData.value ? Math.max(1, Math.ceil(catalogData.value.pagination.total / catalogData.value.pagination.perPage)) : 1)
const currentPage = computed(() => catalogData.value?.pagination.page ?? 1)
const paginationItems = computed(() => {
  const total = totalPages.value
  if (total <= 10) return Array.from({ length: total }, (_, index) => ({ key: `page-${index + 1}`, page: index + 1 }))

  const current = Math.min(Math.max(currentPage.value, 1), total)
  let candidates: number[]
  if (current < 5) {
    candidates = [1, 2, 3, 4, 5, 6, 7, total - 1, total]
  } else if (total - 4 > current && current > 4) {
    candidates = [1, 2, current - 2, current - 1, current, current + 1, current + 2, total - 1, total]
  } else {
    candidates = [1, 2, total - 6, total - 5, total - 4, total - 3, total - 2, total - 1, total]
  }
  const pages = [...new Set(candidates)].filter((pageNumber) => pageNumber >= 1 && pageNumber <= total).sort((a, b) => a - b)
  const items: Array<{ key: string; page: number | null }> = []
  pages.forEach((pageNumber, index) => {
    const previous = pages[index - 1]
    if (previous && pageNumber - previous > 1) items.push({ key: `ellipsis-${previous}`, page: null })
    items.push({ key: `page-${pageNumber}`, page: pageNumber })
  })
  return items
})

function pageLink(nextPage: number) {
  return { path: selfPath.value, query: { ...query.value, page: String(nextPage) } }
}
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader v-if="siteData" :site="siteData" />

    <div class="border-b border-slate-200/70 bg-slate-50/80">
      <div class="pts-container">
        <SiteBreadcrumb :items="catalogBreadcrumbItems" />
      </div>
    </div>

    <CatalogMasthead :heading="heading" :description="bannerDescription" :hero="catalogData?.categoryHero ?? null" />

    <!--
      Khung trang dùng chung .pts-container (max-w 1680px, gutter đáp ứng theo
      breakpoint) thẳng mép dọc với Header/Breadcrumb/Footer và không tràn ngang
      trên màn hình 320px.
    -->
    <main
      class="pts-container grid grid-cols-1 gap-x-[30px] gap-y-6 pt-6 pb-16 max-[1199px]:gap-x-6 max-[769px]:gap-y-4 max-[769px]:pt-5 max-ph:pb-12"
      :class="hasSidebar ? 'min-[1002px]:grid-cols-[240px_minmax(0,1fr)] min-[1200px]:grid-cols-[280px_minmax(0,1fr)]' : ''"
    >
      <nav v-if="categoryNavItems.length" ref="categoryNavRef" class="col-span-full mt-0 flex gap-[10px] overflow-x-auto scroll-smooth overscroll-x-contain pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden max-[769px]:gap-[7px]" aria-label="Danh mục sản phẩm">
        <NuxtLink
          :to="categoryAllHref"
          class="inline-flex min-h-[58px] min-w-[126px] flex-1 items-center justify-center gap-2 rounded-chip border px-3 py-2 text-center text-[13px] font-bold no-underline transition-colors max-[769px]:h-[50px] max-[769px]:min-h-0 max-[769px]:w-[145px] max-[769px]:min-w-0 max-[769px]:flex-none max-[769px]:overflow-hidden"
          :class="!selectedCategory || selectedCategory.url === activeCategoryRoot?.url ? 'border-transparent bg-gold-500 text-navy-950' : 'border-slate-200 bg-white text-navy-800 hover:border-gold-600 hover:text-gold-700'"
          :aria-current="!selectedCategory || selectedCategory.url === activeCategoryRoot?.url ? 'page' : undefined"
        >
          <CatalogMenuIcon class="h-5 w-5 flex-none" name="light" />
          <span class="min-w-0 whitespace-normal [overflow-wrap:anywhere] max-[769px]:line-clamp-2">Tất cả</span>
        </NuxtLink>
        <NuxtLink
          v-for="item in categoryNavItems"
          :key="item.url"
          :to="links.generic(item.url)"
          class="inline-flex min-h-[58px] min-w-[126px] flex-1 items-center justify-center gap-2 rounded-chip border px-3 py-2 text-center text-[13px] font-bold no-underline transition-colors max-[769px]:h-[50px] max-[769px]:min-h-0 max-[769px]:w-[145px] max-[769px]:min-w-0 max-[769px]:flex-none max-[769px]:overflow-hidden"
          :class="item.url === selectedCategory?.url ? 'border-transparent bg-gold-500 text-navy-950' : 'border-slate-200 bg-white text-navy-800 hover:border-gold-600 hover:text-gold-700'"
          :aria-current="item.url === selectedCategory?.url ? 'page' : undefined"
        >
          <CatalogMenuIcon class="h-5 w-5 flex-none" :name="categoryIcon(item.slug)" />
          <span class="min-w-0 whitespace-normal [overflow-wrap:anywhere] max-[769px]:line-clamp-2">{{ categoryNavLabel(item) }}</span>
        </NuxtLink>
      </nav>

      <!--
      Danh mục và các bộ lọc nghiệp vụ dùng chung một panel để không lặp lại
      cùng một lựa chọn ở sidebar và phía trên lưới sản phẩm.
      -->
      <div v-if="hasSidebar" class="hidden flex-col gap-6 min-[1002px]:flex">
        <CatalogFilterSidebar
          id-prefix="catalog-filter-sidebar"
          :categories="sidebarCategories"
          :selected-category="selectedCategory?.url ?? ''"
          :filters="catalogData?.filters ?? emptyFilters"
          :selected="catalogData?.selected ?? emptySelected"
          :keyword="keyword"
          :subcategory-title="subcategoryTitle"
          :filter-link="filterLink"
        />
      </div>

      <section aria-label="Danh sách sản phẩm">
        <div v-if="catalogPending" class="py-16" role="status" aria-live="polite">
          <p class="text-slate-500">Đang tải sản phẩm…</p>
        </div>

        <div v-else-if="catalogError" class="py-16" role="status">
          <h2 class="mb-2 text-xl font-extrabold">Không thể tải danh mục</h2>
          <p class="text-slate-500">Dữ liệu PHP local chưa khả dụng hoặc không khớp contract. Preview không hiển thị dữ liệu giả.</p>
        </div>

        <template v-else-if="catalogData">
          <!--
            Đo trên ảnh production: hàng "Danh sách sản phẩm" + dropdown "Sắp
            xếp" nằm ngay trên lưới, canh hai đầu. Dropdown điều hướng trực
            tiếp (đổi query 'sort'), không phải form - khớp cách pageLink/
            filterLink còn lại của trang đều là NuxtLink đổi URL.
          -->
          <div class="mb-5 flex flex-wrap items-center justify-between gap-3 max-[769px]:items-start max-[769px]:justify-start">
            <h2 class="m-0 text-lg font-black text-navy-800">Danh sách sản phẩm</h2>
            <div
              class="flex items-center gap-2 max-[769px]:grid max-[769px]:w-full"
              :class="hasSidebar ? 'max-[769px]:grid-cols-2' : 'max-[769px]:grid-cols-1'"
            >
              <UButton
                v-if="hasSidebar"
                color="secondary"
                variant="outline"
                size="md"
                class="min-h-11 w-full gap-2 rounded-[4px] border border-slate-300 bg-white px-3 text-sm font-bold text-navy-800 hover:border-navy-800 min-[1002px]:hidden"
                type="button"
                aria-haspopup="dialog"
                aria-controls="catalog-filter-drawer"
                :aria-expanded="filterDrawerOpen"
                @click="openFilterDrawer"
              >
                <UIcon name="i-lucide-sliders-horizontal" class="size-4" aria-hidden="true" />
                Bộ lọc
              </UButton>
              <label v-if="catalogData.selected" class="flex min-w-0 items-center gap-2 text-sm text-slate-500 max-[769px]:w-full">
                <span class="hidden tb:inline">Sắp xếp</span>
                <select
                  class="h-11 w-[150px] min-w-0 rounded-btn border border-gray-300 bg-white px-3 font-normal text-navy-800 max-[769px]:w-full"
                  :value="catalogData.selected.sort"
                  @change="onSortChange"
                >
                  <option v-for="option in sortOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
              </label>
            </div>
          </div>

          <CatalogProductGrid :products="catalogData.products" />

          <nav v-if="totalPages > 1" class="mt-10 flex flex-wrap justify-center gap-2" aria-label="Phân trang sản phẩm">
            <SitePaginationButton
              v-if="currentPage > 1"
              :to="pageLink(1)"
            >
              Đầu
            </SitePaginationButton>
            <SitePaginationButton
              v-if="currentPage > 1"
              :to="pageLink(currentPage - 1)"
            >
              Trước
            </SitePaginationButton>
            <template v-for="item in paginationItems" :key="item.key">
              <span
                v-if="item.page === null"
                class="catalog-pagination__ellipsis text-gold-700"
                aria-hidden="true"
              >...</span>
              <SitePaginationButton
                v-else-if="item.page === currentPage"
                active
                :aria-label="`Trang ${item.page}`"
              >
                {{ item.page }}
              </SitePaginationButton>
              <SitePaginationButton
                v-else
                :to="pageLink(item.page)"
                :aria-label="`Trang ${item.page}`"
              >
                {{ item.page }}
              </SitePaginationButton>
            </template>
            <SitePaginationButton
              v-if="currentPage < totalPages"
              :to="pageLink(currentPage + 1)"
            >
              Sau
            </SitePaginationButton>
            <SitePaginationButton
              v-if="currentPage < totalPages"
              :to="pageLink(totalPages)"
            >
              Cuối
            </SitePaginationButton>
          </nav>
        </template>
      </section>

      <section v-if="catalogData?.categoryContent.applications.length" class="col-span-full mt-[18px]" aria-labelledby="catalog-applications-title">
        <h2 id="catalog-applications-title" class="mb-6 text-center text-[22px] font-black leading-[1.25] text-navy-950">{{ catalogData.categoryContent.title || 'Ứng dụng phù hợp' }}</h2>
        <div class="grid auto-cols-[min(82vw,320px)] grid-flow-col gap-4 overflow-x-auto pr-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden max-[767px]:snap-x max-[767px]:snap-proximity min-[768px]:grid-flow-row min-[768px]:grid-cols-2 min-[768px]:gap-7 min-[768px]:overflow-visible min-[768px]:pr-0 min-[1200px]:grid-cols-3">
          <a v-for="item in catalogData.categoryContent.applications" :key="item.title" :href="item.href ? links.generic(item.href) : undefined" class="min-w-0 text-navy-800 no-underline max-[767px]:snap-start">
            <img v-if="item.image" :src="item.image" :alt="item.title" width="760" height="475" sizes="(min-width: 1200px) 33vw, (min-width: 768px) 50vw, 82vw" class="aspect-[8/5] w-full object-cover" loading="lazy" decoding="async">
            <h3 class="mb-0 mt-3 text-lg font-extrabold text-navy-950">{{ item.title }}</h3>
            <p v-if="item.description" class="mb-0 mt-1 text-base leading-relaxed text-slate-600">{{ item.description }}</p>
          </a>
        </div>
      </section>

      <section v-if="catalogData?.categoryContent.faq.length" class="col-span-full py-8" aria-labelledby="catalog-faq-title">
        <h2 id="catalog-faq-title" class="mb-5 text-2xl font-black text-navy-950">Câu hỏi thường gặp</h2>
        <details v-for="item in catalogData.categoryContent.faq" :key="item.question" class="border-b border-gray-200 py-4">
          <summary class="cursor-pointer font-bold text-navy-950">{{ item.question }}</summary>
          <p class="mb-0 mt-3 leading-relaxed text-slate-600">{{ item.answer }}</p>
        </details>
      </section>

      <section v-if="catalogData?.support" class="relative isolate col-span-full mt-5 grid grid-cols-1 items-center gap-6 overflow-hidden bg-navy-950 px-6 py-8 text-white tb:grid-cols-[minmax(0,1fr)_auto] nav:px-10 nav:py-9" aria-labelledby="catalog-support-title">
        <img v-if="catalogData.support.image" :src="catalogData.support.image" alt="" width="1600" height="500" sizes="100vw" class="absolute inset-0 -z-20 h-full w-full object-cover opacity-30" loading="lazy" decoding="async">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-r from-navy-950/95 via-navy-900/90 to-navy-800/70" aria-hidden="true" />
        <div>
          <h2 id="catalog-support-title" class="mb-2 text-2xl font-black text-white">{{ catalogData.support.title }}</h2>
          <p class="mb-0 max-w-[720px] leading-relaxed text-blue-100/95">{{ catalogData.support.description }}</p>
        </div>
        <NuxtLink :to="links.generic(catalogData.support.href || '/lien-he/')" class="inline-flex min-h-11 items-center justify-center rounded-btn bg-hdr-gold px-6 text-sm font-extrabold text-navy-950 no-underline transition-colors hover:bg-hdr-gold-hover">Nhận tư vấn →</NuxtLink>
      </section>
    </main>

    <UDrawer
      v-if="hasSidebar"
      v-model:open="filterDrawerOpen"
      direction="left"
      title="Bộ lọc sản phẩm"
      description="Chọn danh mục và các tiêu chí để lọc sản phẩm."
      :handle="false"
      :close="false"
      :ui="{
        overlay: 'bg-slate-950/60',
        content: '!h-dvh !max-h-none !w-[min(340px,92vw)] !max-w-none !rounded-none overflow-hidden border-0 bg-white p-0 text-navy-800 shadow-2xl ring-0',
      }"
    >
      <template #content>
        <div id="catalog-filter-drawer" class="flex h-full min-h-0 flex-col" @click="closeFilterDrawerAfterNavigation">
          <div class="sticky top-0 z-10 flex min-h-[62px] flex-none items-center justify-between border-b border-slate-200 bg-white px-4">
            <h2 class="m-0 text-base font-black text-navy-800">Bộ lọc sản phẩm</h2>
            <UButton
              type="button"
              color="neutral"
              variant="ghost"
              class="inline-grid h-11 w-11 place-items-center rounded-btn border border-slate-200 bg-white p-0 text-navy-800 hover:bg-slate-100"
              aria-label="Đóng bộ lọc"
              @click="closeFilterDrawer"
            >
              <UIcon name="i-lucide-x" class="size-5" aria-hidden="true" />
            </UButton>
          </div>
          <div class="min-h-0 flex-1 overflow-y-auto">
            <CatalogFilterSidebar
              id-prefix="catalog-filter-drawer"
              :categories="sidebarCategories"
              :selected-category="selectedCategory?.url ?? ''"
              :filters="catalogData?.filters ?? emptyFilters"
              :selected="catalogData?.selected ?? emptySelected"
              :keyword="keyword"
              :subcategory-title="subcategoryTitle"
              :filter-link="filterLink"
              hide-title
              drawer
            />
          </div>
        </div>
      </template>
    </UDrawer>

    <SiteFooter v-if="siteData" :site="siteData" />
  </div>
</template>
