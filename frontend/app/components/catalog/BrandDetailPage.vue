<script setup lang="ts">
import type { LocationQueryRaw } from 'vue-router'
import type { PublicBrandDetailPayload } from '#shared/contracts/public-brand-detail'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSeoIndexable, publicSeoRobots } from '#shared/public-seo'
import { publicPageQueryValue } from '#shared/public-pagination'
import CatalogProductGrid from '~/components/catalog/CatalogProductGrid.vue'

/*
 * Shared by the public brand route and the noindex preview route. The wrapper
 * supplies `slug`; product totals, taxonomy filters and prices stay bound to
 * the validated CMS DTO.
 */
const props = defineProps<{ slug: string }>()

const route = useRoute()
const selfPath = computed(() => route.path)
const { generic: publicHref } = usePublicLinks()
const { openConsultation } = useStorefrontModal()
const config = useRuntimeConfig()
const page = computed(() => publicPageQueryValue(route.query.page, route.query.p))
const sort = computed(() => typeof route.query.sort === 'string' ? route.query.sort : '')
const price = computed(() => typeof route.query.price === 'string' ? route.query.price : '')
const query = computed(() => ({
  slug: props.slug,
  ...(page.value ? { page: page.value } : {}),
  ...(sort.value ? { sort: sort.value } : {}),
  ...(price.value ? { price: price.value } : {}),
}))

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_api/site', { default: () => fallbackSite })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)

const { data, error, pending } = await useAsyncData<PublicBrandDetailPayload>(
  () => `brand-detail-${props.slug}`,
  () => $fetch('/_api/brand-detail', { query: query.value }),
  { watch: [query] },
)

const brandErrorStatus = (error.value as { statusCode?: unknown } | null)?.statusCode
if (import.meta.server && (error.value || !data.value)) {
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
  return data.value?.pricesVisible ? options : options.filter((option) => option.value !== 'price' && option.value !== 'price-desc')
})
function sortLink(value: string) {
  const next = { ...route.query }
  delete next.page
  delete next.p
  if (value === 'default') delete next.sort
  else next.sort = value
  return { path: selfPath.value, query: next }
}
function onSortChange(value: string) {
  navigateTo(sortLink(value))
}
function priceLink(key: string) {
  const selected = new Set(data.value?.selected.priceKeys ?? [])
  if (selected.has(key as '1' | '2' | '3')) selected.delete(key as '1' | '2' | '3')
  else selected.add(key as '1' | '2' | '3')
  const next = { ...route.query }
  delete next.page
  delete next.p
  if (selected.size) next.price = [...selected].sort().join(',')
  else delete next.price
  return { path: selfPath.value, query: next }
}
function resetFilterLink() {
  const next = { ...route.query }
  delete next.page
  delete next.p
  delete next.price
  return { path: selfPath.value, query: next }
}
function pageLink(nextPage: number) {
  const next: LocationQueryRaw = { ...route.query, page: String(nextPage) }
  delete next.p
  return { path: selfPath.value, query: next }
}

const brandIndexable = computed(() =>
  isPublicSeoIndexable(config.public.brandDetailPublicRouteIndexable, true)
  && !!data.value && !error.value && !['page', 'p', 'sort', 'price'].some((key) => key in route.query))
const brandTitle = computed(() => data.value?.seo?.title || (data.value ? `${data.value.brand.name} | Phúc Thịnh Solar` : 'Thương hiệu — Phúc Thịnh Solar'))
const brandDescription = computed(() => data.value?.seo?.description || '')
const brandSocialDescription = computed(() => brandDescription.value || site.value.brand.name || 'Phúc Thịnh Solar')
const brandKeywords = computed(() => data.value?.seo?.keywords || '')
const brandSocialImage = computed(() => data.value?.seo?.image || site.value.brand.logo || undefined)
useSeoMeta({
  title: brandTitle,
  description: brandDescription,
  robots: computed(() => publicSeoRobots(brandIndexable.value, !!data.value && !error.value, ['page', 'p', 'sort', 'price'].some((key) => key in route.query))),
})
useHead({ meta: computed(() => brandKeywords.value ? [{ name: 'keywords', content: brandKeywords.value }] : []) })
usePublicSocialMeta({
  enabled: computed(() => !!data.value && !error.value),
  title: brandTitle,
  description: brandSocialDescription,
  canonicalPath: computed(() => route.path),
  image: brandSocialImage,
})
</script>

<template>
  <div class="text-navy-800">

    <div class="border-b border-slate-200/70 bg-slate-50/80">
      <div class="pts-container">
        <SiteBreadcrumb :items="[...(data ? [{ label: data.brand.name }] : [])]" />
      </div>
    </div>

    <main :aria-busy="pending">
      <div v-if="pending && !data" class="pts-container min-h-[45vh] py-12" role="status" aria-live="polite">
        <p class="text-slate-500">Đang tải sản phẩm…</p>
      </div>

      <div v-else-if="!data" class="pts-container min-h-[45vh] py-12" :role="error ? 'alert' : 'status'">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Thương hiệu</h1>
        <p class="text-slate-500">Không tìm thấy thương hiệu hoặc dữ liệu tạm thời chưa tải được. Vui lòng thử lại sau ít phút.</p>
      </div>

      <div v-else class="pts-container pt-8 pb-16 max-ph:pt-6 max-ph:pb-12">
        <p v-if="error" class="mb-5 rounded-btn bg-amber-50 px-4 py-3 text-sm text-amber-900" role="alert">Không thể cập nhật kết quả. Đang giữ danh sách vừa tải trước đó.</p>
        <h1 class="mb-7 text-[clamp(28px,3.6vw,44px)] font-black leading-[1.15] tracking-tight">Sản phẩm {{ data.brand.name }}</h1>

        <div class="mb-7 grid grid-cols-2 gap-4 border-b border-slate-200/80 pb-5 tb:grid-cols-4" aria-label="Lợi ích khi chọn sản phẩm">
          <div class="flex items-center gap-2.5"><span class="grid h-9 w-9 flex-none place-items-center rounded-chip bg-navy-800/[.05] text-gold-700"><CatalogMenuIcon class="h-5 w-5" name="light" /></span><span><strong class="block text-[13.5px] text-navy-950">Tư vấn lựa chọn</strong><small class="text-xs text-slate-500">Đúng nhu cầu, hiệu quả</small></span></div>
          <div class="flex items-center gap-2.5"><span class="grid h-9 w-9 flex-none place-items-center rounded-chip bg-navy-800/[.05] text-gold-700"><CatalogMenuIcon class="h-5 w-5" name="cog" /></span><span><strong class="block text-[13.5px] text-navy-950">Hỗ trợ kỹ thuật</strong><small class="text-xs text-slate-500">Trong suốt quá trình sử dụng</small></span></div>
          <div class="flex items-center gap-2.5"><span class="grid h-9 w-9 flex-none place-items-center rounded-chip bg-navy-800/[.05] text-gold-700"><CatalogMenuIcon class="h-5 w-5" name="shield" /></span><span><strong class="block text-[13.5px] text-navy-950">Chính sách rõ ràng</strong><small class="text-xs text-slate-500">Minh bạch, dễ hiểu</small></span></div>
          <div class="flex items-center gap-2.5"><span class="grid h-9 w-9 flex-none place-items-center rounded-chip bg-navy-800/[.05] text-gold-700"><CatalogMenuIcon class="h-5 w-5" name="truck" /></span><span><strong class="block text-[13.5px] text-navy-950">Giao hàng toàn quốc</strong><small class="text-xs text-slate-500">Nhanh chóng, an toàn</small></span></div>
        </div>

        <div class="grid grid-cols-1 items-start gap-8 nav:grid-cols-[240px_minmax(0,1fr)]">
          <button
            v-if="site.productCategories.length || data.filters?.brands.length || data.filters?.priceRanges.length"
            class="public-neutral-button flex min-h-11 items-center justify-between py-3 text-left font-bold text-navy-950 nav:hidden"
            type="button"
            :aria-expanded="mobileFiltersExpanded"
            aria-controls="brand-filter-sidebar"
            @click="mobileFiltersExpanded = !mobileFiltersExpanded"
          >
            Bộ lọc sản phẩm
            <span aria-hidden="true">{{ mobileFiltersExpanded ? '−' : '+' }}</span>
          </button>
          <aside v-if="site.productCategories.length || data.filters?.brands.length || data.filters?.priceRanges.length" id="brand-filter-sidebar" class="space-y-6 rounded-btn bg-slate-50 p-5" :class="mobileFiltersExpanded ? 'block' : 'hidden nav:block'" aria-label="Bộ lọc sản phẩm">
            <section v-if="site.productCategories.length" aria-labelledby="brand-category-filter-title">
              <h2 id="brand-category-filter-title" class="mb-3 text-sm font-extrabold text-navy-950">Danh mục sản phẩm</h2>
              <ul class="m-0 list-none space-y-1.5 p-0">
                <li v-for="category in site.productCategories" :key="category.id">
                  <NuxtLink :to="publicHref(category.url)" class="flex items-center justify-between gap-2 rounded-chip py-1 text-sm text-[#20344b] no-underline transition-colors hover:text-gold-700">
                    <span>{{ category.name }}</span>
                    <UIcon name="i-lucide-chevron-right" class="size-3.5 shrink-0 text-slate-400" aria-hidden="true" />
                  </NuxtLink>
                </li>
              </ul>
            </section>

            <section v-if="data.filters?.brands.length" class="border-t border-slate-200/80 pt-5" aria-labelledby="brand-list-filter-title">
              <h2 id="brand-list-filter-title" class="mb-3 text-sm font-extrabold text-navy-950">Thương hiệu</h2>
              <ul class="m-0 list-none space-y-1 p-0">
                <li v-for="brandOption in data.filters.brands" :key="brandOption.id">
                  <NuxtLink
                    :to="publicHref(brandOption.url)"
                    class="flex items-center justify-between rounded-chip px-2.5 py-1.5 text-sm no-underline transition-colors"
                    :class="brandOption.id === data.brand.id ? 'pts-gold-fill font-bold' : 'text-[#20344b] hover:bg-white hover:text-navy-950'"
                    :aria-current="brandOption.id === data.brand.id ? 'page' : undefined"
                  >
                    <span>{{ brandOption.name }}</span>
                    <span v-if="brandOption.id === data.brand.id" class="size-1.5 rounded-full bg-gold-400" aria-hidden="true" />
                  </NuxtLink>
                </li>
              </ul>
            </section>

            <section v-if="data.filters?.priceRanges.length" class="border-t border-slate-200/80 pt-5" aria-labelledby="brand-price-filter-title">
              <h2 id="brand-price-filter-title" class="mb-3 text-sm font-extrabold text-navy-950">Mức giá</h2>
              <ul class="m-0 list-none space-y-2 p-0">
                <li v-for="range in data.filters.priceRanges" :key="range.key">
                  <NuxtLink :to="priceLink(range.key)" class="flex items-center gap-2.5 text-sm text-[#20344b] no-underline hover:text-navy-950">
                    <span class="grid h-[18px] w-[18px] flex-none place-items-center rounded-chip border-[1.5px] border-navy-950" :class="(data.selected.priceKeys ?? []).includes(range.key) ? 'bg-navy-950' : 'bg-white'" aria-hidden="true">
                      <svg v-if="(data.selected.priceKeys ?? []).includes(range.key)" class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7" /></svg>
                    </span>
                    {{ range.label }}
                  </NuxtLink>
                </li>
              </ul>
            </section>

            <NuxtLink v-if="(data.selected.priceKeys ?? []).length" :to="resetFilterLink()" class="inline-block text-sm font-bold text-gold-700 underline underline-offset-2 hover:text-navy-950">Xóa bộ lọc</NuxtLink>
          </aside>

          <section class="min-w-0" aria-label="Sản phẩm thương hiệu" :aria-busy="pending">
            <div class="mb-6 flex flex-col justify-between gap-4 pts-note-dark p-5 text-white sm:flex-row sm:items-center">
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-3">
                  <h2 class="m-h-panel m-0 text-xl font-black tracking-tight text-white sm:text-2xl">Danh sách sản phẩm</h2>
                  <span class="rounded-chip bg-gold-500/20 px-2.5 py-0.5 text-xs font-extrabold text-gold-300">
                    {{ data.pagination.total }} sản phẩm
                  </span>
                </div>
                <p v-if="data.seo?.description" class="m-0 mt-1.5 max-w-2xl text-xs leading-relaxed text-white/75 sm:text-sm">
                  {{ data.seo.description }}
                </p>
              </div>
              <div class="flex min-w-0 flex-wrap items-center gap-3 sm:shrink-0">
                <button
                  type="button"
                  class="inline-flex h-11 cursor-pointer items-center gap-1.5 rounded-btn bg-gold-500 px-4 text-xs font-extrabold text-navy-950 transition-colors hover:bg-gold-400"
                  @click="openConsultation('Thương hiệu: ' + data.brand.name)"
                >
                  <UIcon name="i-lucide-headset" class="size-4" aria-hidden="true" />
                  <span>Báo giá sỉ / dự án</span>
                </button>
                <label class="flex min-w-0 items-center gap-2 text-xs font-semibold text-white/80 max-[639px]:w-full">
                  <span>Sắp xếp</span>
                  <USelect
                    :model-value="data.selected.sort"
                    :items="sortOptions"
                    aria-label="Sắp xếp sản phẩm"
                    color="neutral"
                    variant="none"
                    class="min-w-0 flex-1 sm:w-[170px] sm:flex-none"
                    :ui="{ base: 'h-11 w-full min-w-0 rounded-btn border border-slate-300 bg-white px-3 text-sm font-normal text-navy-800 focus-visible:border-gold-600 focus-visible:ring-2 focus-visible:ring-gold-500/25 m-lg max-tb:rounded-full' }"
                    @update:model-value="onSortChange"
                  />
                </label>
              </div>
            </div>

            <CatalogProductGrid :products="data.products" />

            <nav v-if="totalPages > 1" class="mt-10 flex flex-wrap justify-center gap-2" aria-label="Phân trang sản phẩm">
          <SitePaginationButton
            v-if="currentPage > 1"
            :to="pageLink(currentPage - 1)"
            aria-label="Trang trước"
          >
            <UIcon name="i-lucide-chevron-left" class="size-4" aria-hidden="true" />
          </SitePaginationButton>
          <template v-for="item in paginationItems" :key="item.key">
            <span
              v-if="item.page === null"
              class="text-gold-700"
              aria-hidden="true"
            >…</span>
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
            aria-label="Trang sau"
          >
            <UIcon name="i-lucide-chevron-right" class="size-4" aria-hidden="true" />
          </SitePaginationButton>
            </nav>
          </section>
        </div>

        <PublicCtaBanner
          class="mt-10"
          variant="catalog"
          title-id="brand-support-title"
          title="Cần hỗ trợ chọn sản phẩm phù hợp?"
          description="Chia sẻ nhu cầu để đội ngũ Phúc Thịnh Solar tư vấn cấu hình và báo giá phù hợp."
          :hotline="site.brand.hotline"
          :zalo="site.brand.zaloHref"
          :subject="'Thương hiệu: ' + (data?.brand?.name || '')"
        />
      </div>
    </main>

  </div>
</template>
