<script setup lang="ts">
import type { PublicCatalogPayload } from '#shared/contracts/public-catalog'
import type { PublicSitePayload, PublicProductCategoryNode } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import CatalogCategoryNav from '~/components/catalog/CatalogCategoryNav.vue'
import CatalogFilterSidebar from '~/components/catalog/CatalogFilterSidebar.vue'
import CatalogProductGrid from '~/components/catalog/CatalogProductGrid.vue'
import SiteHeader from '~/components/SiteHeader.vue'
import SiteFooter from '~/components/SiteFooter.vue'

const route = useRoute()
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
  return { path: '/migration-catalog-preview', query: next }
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

/**
 * CatalogCategoryNav tự ẩn khi không có danh mục. Nếu lưới vẫn giữ nguyên hai
 * cột thì phần nội dung rơi vào đúng cột 192px của sidebar và trang co lại còn
 * một dải hẹp — phát hiện khi đo DOM, không thấy được qua ảnh chụp vì local
 * chưa có dữ liệu. Chuyển về một cột khi không có sidebar.
 */
const hasFilters = computed(() => {
  const filters = catalogData.value?.filters
  return !!filters && (filters.subcategories.length > 0 || filters.brands.length > 0 || filters.priceRanges.length > 0)
})
const hasSidebar = computed(() => (siteData.value?.productCategoryTree?.length ?? 0) > 0 || hasFilters.value)
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
const heading = computed(() => catalogData.value?.category && !Array.isArray(catalogData.value.category) ? catalogData.value.category.name : 'Danh mục sản phẩm')
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
  return { path: '/migration-catalog-preview', query: { ...query.value, page: String(nextPage) } }
}

useSeoMeta({ title: 'Danh mục sản phẩm — internal preview', robots: 'noindex, nofollow, noarchive' })
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader v-if="siteData" :site="siteData" />

    <!--
      Khung trang đo từ ảnh production 1920px: container 1680px, gutter 34px ->
      vùng nội dung 154..1766 (rộng 1612). Lưới sản phẩm bắt đầu tại 447 và rộng
      1396, nên cột trái cộng khoảng cách bằng 216 -> cột trái 192px, gap 24px.
      Cột trái ở bản này rộng 260px (CHƯA đo lại theo panel BỘ LỌC thật, xem
      ghi chú tại CatalogFilterSidebar bên dưới) để đủ chỗ cho nhãn thương
      hiệu/mức giá dài hơn so với danh mục đơn ở CatalogCategoryNav.
    -->
    <main
      class="mx-auto grid w-full max-w-(--container-page) grid-cols-1 gap-6 px-(--spacing-gutter) pt-8 pb-16
             max-ph:pt-6 max-ph:pb-12"
      :class="hasSidebar ? 'nav:grid-cols-[260px_minmax(0,1fr)]' : ''"
    >
      <!--
        CatalogCategoryNav (danh mục cấp 1) và CatalogFilterSidebar (loại sản
        phẩm cấp 2 trong danh mục đang chọn/thương hiệu/giá) là hai khối tách
        rời ở đây. Ảnh production gộp cả hai vào MỘT panel "BỘ LỌC" xám duy
        nhất (mục đầu tiên đổi nội dung theo ngữ cảnh: danh mục cấp 1 ở trang
        gốc, loại sản phẩm con khi đã chọn danh mục) kèm hai tab vàng lặp lại
        danh mục cấp 1 phía trên lưới. Gộp lại cho đúng 100% là việc tách
        riêng (đổi cấu trúc CatalogCategoryNav đang chạy tốt) — ở đây ưu tiên
        làm cho lọc thương hiệu/giá/loại sản phẩm hoạt động đúng trước.
      -->
      <div v-if="hasSidebar" class="flex flex-col gap-6">
        <CatalogCategoryNav :categories="siteData?.productCategoryTree ?? []" :selected="selectedCategory?.url ?? ''" />
        <CatalogFilterSidebar
          v-if="catalogData?.filters && catalogData?.selected"
          :filters="catalogData.filters"
          :selected="catalogData.selected"
          :filter-link="filterLink"
        />
      </div>

      <section aria-labelledby="catalog-preview-title">
        <p class="mb-5 text-slate-500">
          <NuxtLink class="text-inherit" to="/migration-catalog-preview">Danh mục sản phẩm</NuxtLink>
          <span v-if="selectedCategory"> / {{ selectedCategory.name }}</span>
        </p>

        <h1 id="catalog-preview-title" class="mb-3 text-[clamp(28px,4vw,48px)] font-black leading-[1.15] tracking-tight">
          {{ heading }}
        </h1>

        <p class="mb-7 leading-relaxed text-slate-500">
          Preview nội bộ từ dữ liệu CMS. Giá công khai/giá báo đã nối theo authority PHP; lọc theo loại sản phẩm/thương hiệu/mức giá và sắp xếp đã nối với cùng truy vấn PHP. Tồn kho và merchandising sort theo nhóm đèn ưu tiên vẫn đang đối chiếu.
        </p>

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
    </main>

    <SiteFooter v-if="siteData" :site="siteData" />
  </div>
</template>
