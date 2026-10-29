<script setup lang="ts">
import type { PublicBrandDetailPayload } from '#shared/contracts/public-brand-detail'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { isRuntimeFlagEnabled } from '#shared/public-route-gate'
import { publicSeoRobots } from '#shared/public-seo'
import CatalogProductGrid from '~/components/catalog/CatalogProductGrid.vue'

/*
 * Tách từ migration-brand-detail-preview.vue (xem đó + pages/thuong-hieu/
 * [slug].vue) để dùng chung đúng một logic cho cả preview noindex và route
 * public thật - không nhân bản. Nhận `slug` qua prop vì 2 trang lấy slug từ
 * 2 nguồn khác nhau (preview: ?slug=, public: /thuong-hieu/{slug}/ param).
 *
 * Verify thật trên PHP local (curl http://127.0.0.1:8080/thuong-hieu/longi/,
 * 2026-10-28) sau khi Lane 1 visual audit báo lệch: trang dùng CHUNG template
 * "product/product" với catalog, và KHÔNG đổi H1 theo brand - H1 thật vẫn là
 * "Sản phẩm năng lượng mặt trời" (class catalog-heading), breadcrumb chỉ 2
 * cấp "Trang chủ / {Tên thương hiệu}" (không có "Sản phẩm" ở giữa), có dải
 * benefit tĩnh 4 mục (class .filter_product, text "Tư vấn lựa chọn / Hỗ trợ
 * kỹ thuật / Chính sách rõ ràng / Giao hàng toàn quốc" - giống hệt text tĩnh
 * đã dùng ở CatalogPage.vue) và khối CTA cuối trang .pts-catalog-support với
 * text fallback mặc định "Cần hỗ trợ chọn sản phẩm phù hợp?" (PHP:
 * sources/product.php - không đổi theo brand khi Admin chưa tự soạn ảnh
 * loại "catalog-campaign" cho brand, nên không coi là dữ liệu bịa khi hardcode
 * tĩnh ở đây).
 *
 * Sidebar filter dùng taxonomy và brand rows thật từ các read-only DTO; mức
 * giá chỉ xuất hiện khi public pricing đang bật và được áp dụng trong brand
 * hiện tại ở pts_v1_brand_detail.php.
 */
const props = defineProps<{ slug: string }>()

const route = useRoute()
const selfPath = computed(() => route.path)
const { active: isMigrationPreview, generic: migrationHref } = useMigrationLinks()
const config = useRuntimeConfig()
const publicEnabled = computed(() => !isMigrationPreview.value && isRuntimeFlagEnabled(config.public.brandDetailPublicRouteEnabled))
const page = computed(() => typeof route.query.page === 'string' ? route.query.page : '')
const sort = computed(() => typeof route.query.sort === 'string' ? route.query.sort : '')
const price = computed(() => typeof route.query.price === 'string' ? route.query.price : '')
const query = computed(() => ({
  slug: props.slug,
  ...(page.value ? { page: page.value } : {}),
  ...(sort.value ? { sort: sort.value } : {}),
  ...(price.value ? { price: price.value } : {}),
}))

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)

const { data, error, pending } = await useAsyncData<PublicBrandDetailPayload>(
  () => `migration-brand-detail-${JSON.stringify(query.value)}`,
  () => $fetch('/_migration/brand-detail', { query: query.value }),
  { watch: [query] },
)

const brandErrorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
if (import.meta.server && publicEnabled.value && (error.value || !data.value)) {
  const status = brandErrorStatus === 404 ? 404 : 502
  setResponseStatus(status, status === 404 ? 'Not Found' : 'Bad Gateway')
}

const totalPages = computed(() => data.value ? Math.max(1, Math.ceil(data.value.pagination.total / data.value.pagination.perPage)) : 1)
const currentPage = computed(() => data.value?.pagination.page ?? 1)
const paginationItems = usePaginationItems(currentPage, totalPages)
const mobileFiltersExpanded = ref(false)
const sortOptions = computed(() => {
  const options = [
    { value: 'default', label: 'Mặc định' },
    { value: 'abc', label: 'Tên A-Z' },
    { value: 'abc-desc', label: 'Tên Z-A' },
    { value: 'price', label: 'Giá tăng dần' },
    { value: 'price-desc', label: 'Giá giảm dần' },
    { value: 'new', label: 'Mới nhất' },
    { value: 'new-desc', label: 'Cũ nhất' },
  ]
  return data.value?.parity.prices.included ? options : options.filter((option) => option.value !== 'price' && option.value !== 'price-desc')
})
function sortLink(value: string) {
  const next = { ...route.query }
  delete next.page
  if (value === 'default') delete next.sort
  else next.sort = value
  return { path: selfPath.value, query: next }
}
function onSortChange(event: Event) {
  navigateTo(sortLink((event.target as HTMLSelectElement).value))
}
function priceLink(key: string) {
  const selected = new Set(data.value?.selected.priceKeys ?? [])
  if (selected.has(key as '1' | '2' | '3')) selected.delete(key as '1' | '2' | '3')
  else selected.add(key as '1' | '2' | '3')
  const next = { ...route.query }
  delete next.page
  if (selected.size) next.price = [...selected].sort().join(',')
  else delete next.price
  return { path: selfPath.value, query: next }
}
function resetFilterLink() {
  const next = { ...route.query }
  delete next.page
  delete next.price
  return { path: selfPath.value, query: next }
}
function pageLink(nextPage: number) {
  return { path: selfPath.value, query: { ...route.query, page: String(nextPage) } }
}

const brandIndexable = computed(() => publicEnabled.value
  && isRuntimeFlagEnabled(config.public.brandDetailPublicRouteIndexable)
  && !!data.value && !error.value && !['page', 'sort', 'price'].some((key) => key in route.query))
const brandTitle = computed(() => data.value ? `${data.value.brand.name} — Phúc Thịnh Solar` : 'Thương hiệu — Phúc Thịnh Solar')
useSeoMeta({
  title: brandTitle,
  robots: computed(() => publicSeoRobots(brandIndexable.value, !!data.value && !error.value, ['page', 'sort', 'price'].some((key) => key in route.query))),
})
usePublicSocialMeta({
  enabled: computed(() => publicEnabled.value && !!data.value && !error.value),
  title: brandTitle,
  description: 'Khám phá sản phẩm chính hãng tại Phúc Thịnh Solar.',
  canonicalPath: computed(() => route.path),
})
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <div class="border-b border-slate-200/70 bg-slate-50/80">
      <div class="pts-container">
        <SiteBreadcrumb :items="[...(data ? [{ label: data.brand.name }] : [])]" />
      </div>
    </div>

    <main>
      <div v-if="pending" class="pts-container min-h-[45vh] py-12" role="status" aria-live="polite">
        <p class="text-slate-500">Đang tải sản phẩm…</p>
      </div>

      <div v-else-if="error || !data" class="pts-container min-h-[45vh] py-12" role="status">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Thương hiệu</h1>
        <p class="text-slate-500">Không tìm thấy thương hiệu hoặc dữ liệu PHP local chưa khả dụng. Preview không hiển thị dữ liệu giả.</p>
      </div>

      <div v-else class="pts-container pt-8 pb-16 max-ph:pt-6 max-ph:pb-12">
        <!-- H1 khớp PHP thật: trang brand KHÔNG đổi heading theo tên thương
             hiệu, giữ nguyên tiêu đề catalog chung (xem comment đầu file). -->
        <h1 class="mb-7 text-[clamp(28px,3.6vw,44px)] font-black leading-[1.15] tracking-tight">Sản phẩm năng lượng mặt trời</h1>

        <div class="mb-7 grid grid-cols-2 gap-4 border-b border-slate-200/80 pb-5 tb:grid-cols-4" aria-label="Lợi ích khi chọn sản phẩm">
          <div class="flex items-center gap-2.5"><span class="grid h-9 w-9 flex-none place-items-center rounded-chip bg-navy-800/[.05] text-gold-700"><CatalogMenuIcon class="h-5 w-5" name="light" /></span><span><strong class="block text-[13.5px] text-navy-950">Tư vấn lựa chọn</strong><small class="text-xs text-slate-500">Đúng nhu cầu, hiệu quả</small></span></div>
          <div class="flex items-center gap-2.5"><span class="grid h-9 w-9 flex-none place-items-center rounded-chip bg-navy-800/[.05] text-gold-700"><CatalogMenuIcon class="h-5 w-5" name="cog" /></span><span><strong class="block text-[13.5px] text-navy-950">Hỗ trợ kỹ thuật</strong><small class="text-xs text-slate-500">Trong suốt quá trình sử dụng</small></span></div>
          <div class="flex items-center gap-2.5"><span class="grid h-9 w-9 flex-none place-items-center rounded-chip bg-navy-800/[.05] text-gold-700"><CatalogMenuIcon class="h-5 w-5" name="shield" /></span><span><strong class="block text-[13.5px] text-navy-950">Chính sách rõ ràng</strong><small class="text-xs text-slate-500">Minh bạch, dễ hiểu</small></span></div>
          <div class="flex items-center gap-2.5"><span class="grid h-9 w-9 flex-none place-items-center rounded-chip bg-navy-800/[.05] text-gold-700"><CatalogMenuIcon class="h-5 w-5" name="truck" /></span><span><strong class="block text-[13.5px] text-navy-950">Giao hàng toàn quốc</strong><small class="text-xs text-slate-500">Nhanh chóng, an toàn</small></span></div>
        </div>

        <div class="grid grid-cols-1 items-start gap-8 nav:grid-cols-[240px_minmax(0,1fr)]">
          <button
            v-if="site.productCategories.length || data.filters?.brands.length || data.filters?.priceRanges.length"
            class="flex min-h-11 items-center justify-between border-y border-gray-200 py-3 text-left font-bold text-navy-950 nav:hidden"
            type="button"
            :aria-expanded="mobileFiltersExpanded"
            aria-controls="brand-filter-sidebar"
            @click="mobileFiltersExpanded = !mobileFiltersExpanded"
          >
            Bộ lọc sản phẩm
            <span aria-hidden="true">{{ mobileFiltersExpanded ? '−' : '+' }}</span>
          </button>
          <aside v-if="site.productCategories.length || data.filters?.brands.length || data.filters?.priceRanges.length" id="brand-filter-sidebar" class="space-y-7" :class="mobileFiltersExpanded ? 'block' : 'hidden nav:block'" aria-label="Bộ lọc sản phẩm">
            <section v-if="site.productCategories.length" aria-labelledby="brand-category-filter-title">
              <h2 id="brand-category-filter-title" class="mb-3 text-sm font-bold text-navy-950">Danh mục sản phẩm</h2>
              <ul class="m-0 list-none space-y-2 p-0">
                <li v-for="category in site.productCategories" :key="category.id">
                  <NuxtLink :to="migrationHref(category.url)" class="text-sm text-[#20344b] no-underline hover:text-navy-950">{{ category.name }}</NuxtLink>
                </li>
              </ul>
            </section>

            <section v-if="data.filters?.brands.length" aria-labelledby="brand-list-filter-title">
              <h2 id="brand-list-filter-title" class="mb-3 text-sm font-bold text-navy-950">Thương hiệu</h2>
              <ul class="m-0 list-none space-y-2 p-0">
                <li v-for="brandOption in data.filters.brands" :key="brandOption.id">
                  <NuxtLink
                    :to="migrationHref(brandOption.url)"
                    class="text-sm no-underline hover:text-navy-950"
                    :class="brandOption.id === data.brand.id ? 'font-bold text-navy-950' : 'text-[#20344b]'"
                    :aria-current="brandOption.id === data.brand.id ? 'page' : undefined"
                  >{{ brandOption.name }}</NuxtLink>
                </li>
              </ul>
            </section>

            <section v-if="data.filters?.priceRanges.length" aria-labelledby="brand-price-filter-title">
              <h2 id="brand-price-filter-title" class="mb-3 text-sm font-bold text-navy-950">Mức giá</h2>
              <ul class="m-0 list-none space-y-2 p-0">
                <li v-for="range in data.filters.priceRanges" :key="range.key">
                  <NuxtLink :to="priceLink(range.key)" class="flex items-center gap-2.5 text-sm text-[#20344b] no-underline hover:text-navy-950">
                    <span class="grid h-[18px] w-[18px] flex-none place-items-center rounded-[3px] border-[1.5px] border-navy-950" :class="(data.selected.priceKeys ?? []).includes(range.key) ? 'bg-navy-950' : 'bg-white'" aria-hidden="true">
                      <svg v-if="(data.selected.priceKeys ?? []).includes(range.key)" class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7" /></svg>
                    </span>
                    {{ range.label }}
                  </NuxtLink>
                </li>
              </ul>
            </section>

            <NuxtLink v-if="(data.selected.priceKeys ?? []).length" :to="resetFilterLink()" class="text-sm text-[#20344b] underline underline-offset-2 hover:text-navy-950">Xóa bộ lọc</NuxtLink>
          </aside>

          <section class="min-w-0" aria-label="Sản phẩm thương hiệu">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
              <h2 class="m-0 text-lg font-black text-navy-800">{{ data.brand.name }}</h2>
              <label class="flex items-center gap-2 text-sm text-slate-500">
                Sắp xếp
                <select
                  class="h-11 rounded-chip border border-gray-300 bg-white px-3 font-normal text-navy-800"
                  :value="data.selected.sort"
                  @change="onSortChange"
                >
                  <option v-for="option in sortOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
              </label>
            </div>

            <CatalogProductGrid :products="data.products" />

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
              class="inline-grid min-h-11 min-w-11 place-items-center px-2 py-2 text-slate-500"
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
          </section>
        </div>

        <section class="relative isolate mt-10 grid grid-cols-1 items-center gap-6 overflow-hidden rounded-btn border border-navy-700/60 bg-gradient-to-r from-navy-950 via-navy-900 to-navy-800 px-6 py-8 text-white shadow-md tb:grid-cols-[minmax(0,1fr)_auto] nav:px-10 nav:py-9" aria-labelledby="brand-support-title">
          <div>
            <h2 id="brand-support-title" class="mb-2 text-2xl font-black text-white">Cần hỗ trợ chọn sản phẩm phù hợp?</h2>
            <p class="mb-0 max-w-[720px] leading-relaxed text-blue-100/95">Chia sẻ nhu cầu để đội ngũ Phúc Thịnh Solar tư vấn cấu hình và báo giá phù hợp.</p>
          </div>
          <NuxtLink :to="migrationHref('/lien-he/')" class="inline-flex min-h-11 items-center justify-center rounded-btn bg-hdr-gold px-6 text-sm font-extrabold text-navy-950 no-underline shadow-sm transition-colors hover:bg-hdr-gold-hover">Nhận tư vấn →</NuxtLink>
        </section>
      </div>
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
