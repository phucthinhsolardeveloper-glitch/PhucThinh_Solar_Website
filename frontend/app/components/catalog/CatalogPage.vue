<script setup lang="ts">
import type { PublicCatalogPayload } from '#shared/contracts/public-catalog'
import type { PublicSitePayload, PublicProductCategoryNode } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import CatalogFilterSidebar from '~/components/catalog/CatalogFilterSidebar.vue'
import CatalogProductGrid from '~/components/catalog/CatalogProductGrid.vue'
import SiteHeader from '~/components/SiteHeader.vue'
import SiteFooter from '~/components/SiteFooter.vue'

/*
 * Tách từ migration-catalog-preview.vue (xem đó + pages/san-pham/index.vue)
 * để dùng chung đúng một logic cho cả preview noindex và route public thật -
 * không nhân bản. Link nội bộ dùng route.path (không hardcode) nên component
 * này tự khớp đúng URL của bất kỳ trang nào render nó.
 */
const route = useRoute()
const links = useMigrationLinks()
const selfPath = computed(() => route.path)
const category = computed(() => typeof route.query.category === 'string' ? route.query.category : '')
const page = computed(() => typeof route.query.page === 'string' ? route.query.page : '')
const perPage = computed(() => typeof route.query.per_page === 'string' ? route.query.per_page : '')
const brand = computed(() => typeof route.query.brand === 'string' ? route.query.brand : '')
const price = computed(() => typeof route.query.price === 'string' ? route.query.price : '')
const subcategory = computed(() => typeof route.query.subcategory === 'string' ? route.query.subcategory : '')
const sort = computed(() => typeof route.query.sort === 'string' ? route.query.sort : '')
const query = computed(() => ({
  ...(category.value ? { category: category.value } : {}),
  ...(page.value ? { page: page.value } : {}),
  ...(perPage.value ? { per_page: perPage.value } : {}),
  ...(brand.value ? { brand: brand.value } : {}),
  ...(price.value ? { price: price.value } : {}),
  ...(subcategory.value ? { subcategory: subcategory.value } : {}),
  ...(sort.value ? { sort: sort.value } : {}),
}))

/*
 * Doi filter/sort luon ve trang 1 - giu dung quy uoc pagination hien co
 * (pageLink giu nguyen cac query khac), tranh "trang 3 nhung loc ra 0 ket
 * qua" khi khach doi bo loc o trang sau.
 */
function filterLink(overrides: { subcategory?: string; brand?: string; price?: string; sort?: string }) {
  const next: Record<string, string> = { ...query.value, ...overrides }
  for (const key of Object.keys(next)) if (next[key] === '') delete next[key]
  delete next.page
  return { path: selfPath.value, query: next }
}
function onSortChange(event: Event) {
  const value = (event.target as HTMLSelectElement).value
  navigateTo(filterLink({ sort: value === 'default' ? '' : value }))
}
const { data: siteData } = await useAsyncData<PublicSitePayload>('migration-catalog-site', async () => {
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
const activeCategoryRoot = computed(() => {
  const tree = siteData.value?.productCategoryTree ?? []
  if (!selectedCategory.value) return null
  return tree.find((node) => node.url === selectedCategory.value?.url)
    ?? tree.find((node) => node.children.some((child) => child.url === selectedCategory.value?.url))
})
const categoryNavItems = computed(() => selectedCategory.value
  ? activeCategoryRoot.value?.children ?? []
  : (siteData.value?.productCategoryTree ?? []).filter((node) => node.slug !== 'den-san-vuon'))
const categoryAllHref = computed(() => links.generic(activeCategoryRoot.value?.url ?? '/san-pham/'))

/**
 * Nếu lưới vẫn giữ nguyên hai cột khi không có danh mục/bộ lọc thì nội dung
 * rơi vào cột sidebar và trang co lại thành một dải hẹp. Chỉ tạo cột trái khi
 * panel có dữ liệu thực.
 */
const hasFilters = computed(() => {
  const filters = catalogData.value?.filters
  return !!filters && (filters.subcategories.length > 0 || filters.brands.length > 0 || filters.priceRanges.length > 0)
})
const hasSidebar = computed(() => (siteData.value?.productCategoryTree?.length ?? 0) > 0 || hasFilters.value)
const emptyFilters: NonNullable<PublicCatalogPayload['filters']> = { brands: [], priceRanges: [], subcategories: [] }
const emptySelected: NonNullable<PublicCatalogPayload['selected']> = { brandIds: [], priceKeys: [], subcategoryIds: [], sort: 'default' }
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
  if (total <= 7) return Array.from({ length: total }, (_, index) => ({ key: `page-${index + 1}`, page: index + 1 }))

  const current = Math.min(Math.max(currentPage.value, 1), total)
  const candidates = new Set([1, total, current - 1, current, current + 1])
  if (current <= 4) [2, 3, 4, 5].forEach((pageNumber) => candidates.add(pageNumber))
  if (current >= total - 3) [total - 4, total - 3, total - 2, total - 1].forEach((pageNumber) => candidates.add(pageNumber))
  const pages = [...candidates].filter((pageNumber) => pageNumber >= 1 && pageNumber <= total).sort((a, b) => a - b)
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

    <!--
      Khung trang đo từ ảnh production 1920px: container 1680px, gutter 34px ->
      vùng nội dung 154..1766 (rộng 1612). Lưới sản phẩm bắt đầu tại 447 và rộng
      1396, nên cột trái cộng khoảng cách bằng 216 -> cột trái 192px, gap 24px.
      Cột trái ở bản này rộng 260px để đủ chỗ cho nhãn thương hiệu/mức giá dài.
    -->
    <main
      class="mx-auto grid w-full max-w-(--container-page) grid-cols-1 gap-6 px-(--spacing-gutter) pt-8 pb-16
             max-ph:pt-6 max-ph:pb-12"
      :class="hasSidebar ? 'nav:grid-cols-[260px_minmax(0,1fr)]' : ''"
    >
      <section class="col-span-full overflow-hidden bg-navy-950 text-white" aria-labelledby="catalog-heading">
        <div class="relative isolate flex min-h-[clamp(260px,24vw,410px)] items-end overflow-hidden px-6 py-8 nav:px-10 nav:py-10">
          <img v-if="catalogData?.categoryHero?.image" :src="catalogData.categoryHero.image" :alt="catalogData.categoryHero.alt" class="absolute inset-0 -z-20 h-full w-full object-cover" fetchpriority="high">
          <span class="absolute inset-0 -z-10 bg-gradient-to-r from-navy-950/90 via-navy-950/65 to-navy-950/15" aria-hidden="true" />
          <div class="max-w-[760px]">
            <h1 id="catalog-heading" class="mb-3 text-[clamp(30px,4.2vw,52px)] font-black leading-[1.12] tracking-tight text-white">{{ heading }}</h1>
            <p class="m-0 max-w-[62ch] leading-relaxed text-blue-100">{{ bannerDescription }}</p>
          </div>
        </div>
      </section>

      <div class="col-span-full grid grid-cols-2 gap-4 border-b border-gray-200 py-5 tb:grid-cols-4" aria-label="Lợi ích khi chọn sản phẩm">
        <div class="flex items-start gap-3"><span class="text-gold-700" aria-hidden="true">✦</span><span><strong class="block text-sm text-navy-950">Tư vấn lựa chọn</strong><small class="text-slate-500">Đúng nhu cầu, hiệu quả</small></span></div>
        <div class="flex items-start gap-3"><span class="text-gold-700" aria-hidden="true">⚙</span><span><strong class="block text-sm text-navy-950">Hỗ trợ kỹ thuật</strong><small class="text-slate-500">Trong suốt quá trình sử dụng</small></span></div>
        <div class="flex items-start gap-3"><span class="text-gold-700" aria-hidden="true">✓</span><span><strong class="block text-sm text-navy-950">Chính sách rõ ràng</strong><small class="text-slate-500">Minh bạch, dễ hiểu</small></span></div>
        <div class="flex items-start gap-3"><span class="text-gold-700" aria-hidden="true">↗</span><span><strong class="block text-sm text-navy-950">Giao hàng toàn quốc</strong><small class="text-slate-500">Nhanh chóng, an toàn</small></span></div>
      </div>

      <nav v-if="categoryNavItems.length" class="col-span-full flex gap-3 overflow-x-auto border-b border-gray-200 py-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Danh mục sản phẩm">
        <NuxtLink :to="categoryAllHref" class="flex-none px-3 py-2 font-bold text-navy-800 no-underline hover:text-gold-700" :aria-current="!selectedCategory || selectedCategory.url === activeCategoryRoot?.url ? 'page' : undefined">Tất cả</NuxtLink>
        <NuxtLink v-for="item in categoryNavItems" :key="item.url" :to="links.generic(item.url)" class="flex-none px-3 py-2 font-bold text-navy-800 no-underline hover:text-gold-700" :aria-current="item.url === selectedCategory?.url ? 'page' : undefined">{{ item.name }}</NuxtLink>
      </nav>

      <!--
      Danh mục và các bộ lọc nghiệp vụ dùng chung một panel để không lặp lại
      cùng một lựa chọn ở sidebar và phía trên lưới sản phẩm.
      -->
      <div v-if="hasSidebar" class="flex flex-col gap-6">
        <CatalogFilterSidebar
          :categories="siteData?.productCategoryTree ?? []"
          :selected-category="selectedCategory?.url ?? ''"
          :filters="catalogData?.filters ?? emptyFilters"
          :selected="catalogData?.selected ?? emptySelected"
          :filter-link="filterLink"
        />
      </div>

      <section aria-labelledby="catalog-list-title">
        <p class="mb-5 text-slate-500">
          <NuxtLink class="text-inherit" :to="selfPath">Danh mục sản phẩm</NuxtLink>
          <span v-if="selectedCategory"> / {{ selectedCategory.name }}</span>
        </p>

        <h2 id="catalog-list-title" class="mb-3 text-2xl font-black leading-tight tracking-tight">Sản phẩm trong danh mục</h2>
        <p class="mb-7 leading-relaxed text-slate-500">Lọc theo danh mục, thương hiệu và mức giá để tìm thiết bị phù hợp.</p>

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
          <div v-if="catalogData.selected" class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <h2 class="m-0 text-lg font-black text-navy-800">Danh sách sản phẩm</h2>
            <label class="flex items-center gap-2 text-sm text-slate-500">
              Sắp xếp
              <select
                class="h-11 rounded-chip border border-gray-300 bg-white px-3 font-normal text-navy-800"
                :value="catalogData.selected.sort"
                @change="onSortChange"
              >
                <option v-for="option in sortOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
              </select>
            </label>
          </div>

          <CatalogProductGrid :products="catalogData.products" />

          <!--
            Ảnh production dùng nhãn "1 2 Next Last"; giữ "Trước/Sau" của bản
            preview vì đó là nội dung chứ không phải kiểu dáng — đổi nhãn là đổi
            trải nghiệm, cần duyệt riêng. Ô tối thiểu 44x44 giữ nguyên: đây là
            ngưỡng vùng chạm trên mobile, không phải lựa chọn thẩm mỹ.
          -->
          <nav v-if="totalPages > 1" class="mt-10 flex flex-wrap gap-2" aria-label="Phân trang sản phẩm">
            <NuxtLink
              v-if="currentPage > 1"
              class="inline-grid min-h-11 min-w-11 place-items-center rounded-chip border border-gray-200 px-3 py-2 text-navy-800 no-underline transition-colors hover:border-navy-800"
              :to="pageLink(currentPage - 1)"
            >
              Trước
            </NuxtLink>
            <template v-for="item in paginationItems" :key="item.key">
              <span
                v-if="item.page === null"
                class="catalog-pagination__ellipsis inline-grid min-h-11 min-w-11 place-items-center px-2 py-2 text-slate-500"
                aria-hidden="true"
              >…</span>
              <span
                v-else-if="item.page === currentPage"
                class="inline-grid min-h-11 min-w-11 place-items-center rounded-chip border border-navy-800 bg-navy-800 px-3 py-2 text-white"
                aria-current="page"
                :aria-label="`Trang ${item.page}`"
              >
                {{ item.page }}
              </span>
              <NuxtLink
                v-else
                class="inline-grid min-h-11 min-w-11 place-items-center rounded-chip border border-gray-200 px-3 py-2 text-navy-800 no-underline transition-colors hover:border-navy-800"
                :to="pageLink(item.page)"
                :aria-label="`Trang ${item.page}`"
              >
                {{ item.page }}
              </NuxtLink>
            </template>
            <NuxtLink
              v-if="currentPage < totalPages"
              class="inline-grid min-h-11 min-w-11 place-items-center rounded-chip border border-gray-200 px-3 py-2 text-navy-800 no-underline transition-colors hover:border-navy-800"
              :to="pageLink(currentPage + 1)"
            >
              Sau
            </NuxtLink>
          </nav>
        </template>
      </section>

      <section v-if="catalogData?.categoryContent.applications.length" class="col-span-full py-10" aria-labelledby="catalog-applications-title">
        <h2 id="catalog-applications-title" class="mb-7 text-2xl font-black text-navy-950">{{ catalogData.categoryContent.title || 'Ứng dụng phù hợp' }}</h2>
        <div class="grid grid-cols-1 gap-7 tb:grid-cols-2 nav:grid-cols-3">
          <a v-for="item in catalogData.categoryContent.applications" :key="item.title" :href="item.href ? links.generic(item.href) : undefined" class="min-w-0 text-navy-800 no-underline">
            <img v-if="item.image" :src="item.image" :alt="item.title" class="aspect-[8/5] w-full object-cover" loading="lazy" decoding="async">
            <h3 class="mb-2 mt-4 text-lg font-extrabold text-navy-950">{{ item.title }}</h3>
            <p v-if="item.description" class="m-0 leading-relaxed text-slate-600">{{ item.description }}</p>
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
        <img v-if="catalogData.support.image" :src="catalogData.support.image" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-20" loading="lazy" decoding="async">
        <div>
          <h2 id="catalog-support-title" class="mb-2 text-2xl font-black">{{ catalogData.support.title }}</h2>
          <p class="mb-0 max-w-[720px] leading-relaxed text-blue-100">{{ catalogData.support.description }}</p>
        </div>
        <NuxtLink :to="links.generic(catalogData.support.href || '/lien-he/')" class="inline-flex min-h-11 items-center justify-center bg-gold-500 px-5 font-extrabold text-navy-950 no-underline">Nhận tư vấn →</NuxtLink>
      </section>
    </main>

    <SiteFooter v-if="siteData" :site="siteData" />
  </div>
</template>
