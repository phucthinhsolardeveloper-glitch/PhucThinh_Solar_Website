<script setup lang="ts">
import type { PublicBrandDetailPayload } from '#shared/contracts/public-brand-detail'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import CatalogProductGrid from '~/components/catalog/CatalogProductGrid.vue'

/*
 * Tách từ migration-brand-detail-preview.vue (xem đó + pages/thuong-hieu/
 * [slug].vue) để dùng chung đúng một logic cho cả preview noindex và route
 * public thật - không nhân bản. Nhận `slug` qua prop vì 2 trang lấy slug từ
 * 2 nguồn khác nhau (preview: ?slug=, public: /thuong-hieu/{slug}/ param).
 *
 * KHÔNG có ảnh production cho /thuong-hieu/{slug}/ để đo. Đã đọc kỹ
 * templates/product/product_tpl.php và product_detail_tpl.php (grep
 * "productBrand[") trước khi dựng trang này: trang brand thật dùng CHUNG
 * template "product/product" với catalog, không có logo/mô tả/nội dung
 * thương hiệu nào được render công khai - chỉ đổi H1/breadcrumb thành tên
 * thương hiệu rồi liệt kê đúng lưới sản phẩm đã lọc theo id_brand. Vì vậy
 * trang này tái dùng thẳng CatalogProductGrid.vue (đã đo pixel) thay vì
 * dựng thêm UI riêng chưa có bằng chứng.
 */
const props = defineProps<{ slug: string }>()

const route = useRoute()
const selfPath = computed(() => route.path)
const { generic: migrationHref } = useMigrationLinks()
const page = computed(() => typeof route.query.page === 'string' ? route.query.page : '')
const sort = computed(() => typeof route.query.sort === 'string' ? route.query.sort : '')
const query = computed(() => ({
  slug: props.slug,
  ...(page.value ? { page: page.value } : {}),
  ...(sort.value ? { sort: sort.value } : {}),
}))

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)

const { data, error, pending } = await useAsyncData<PublicBrandDetailPayload>(
  () => `migration-brand-detail-${JSON.stringify(query.value)}`,
  () => $fetch('/_migration/brand-detail', { query: query.value }),
  { watch: [query] },
)

const totalPages = computed(() => data.value ? Math.max(1, Math.ceil(data.value.pagination.total / data.value.pagination.perPage)) : 1)
const currentPage = computed(() => data.value?.pagination.page ?? 1)
const paginationItems = usePaginationItems(currentPage, totalPages)
function pageLink(nextPage: number) {
  return { path: selfPath.value, query: { ...route.query, page: String(nextPage) } }
}

useSeoMeta({ title: computed(() => data.value ? `${data.value.brand.name} — Phúc Thịnh Solar` : 'Thương hiệu — Phúc Thịnh Solar') })
</script>

<template>
  <div class="text-navy-800">
    <SiteHeader :site="site" />

    <main>
      <div class="mx-auto w-full max-w-(--container-page) px-(--spacing-gutter)">
        <SiteBreadcrumb :items="[{ label: 'Sản phẩm', to: migrationHref('/san-pham/') }, ...(data ? [{ label: data.brand.name }] : [])]" />
      </div>

      <div v-if="pending" class="mx-auto min-h-[45vh] w-full max-w-(--container-page) px-(--spacing-gutter) py-12" role="status" aria-live="polite">
        <p class="text-slate-500">Đang tải sản phẩm…</p>
      </div>

      <div v-else-if="error || !data" class="mx-auto min-h-[45vh] w-full max-w-(--container-page) px-(--spacing-gutter) py-12" role="status">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Thương hiệu</h1>
        <p class="text-slate-500">Không tìm thấy thương hiệu hoặc dữ liệu PHP local chưa khả dụng. Preview không hiển thị dữ liệu giả.</p>
      </div>

      <div v-else class="mx-auto w-full max-w-(--container-page) px-(--spacing-gutter) pt-8 pb-16 max-ph:pt-6 max-ph:pb-12">
        <h1 class="mb-7 text-[clamp(28px,4vw,48px)] font-black leading-[1.15] tracking-tight">{{ data.brand.name }}</h1>

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
      </div>
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
