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
 * Sidebar bộ lọc (.lv-filter) CŨNG có trên PHP thật nhưng /_migration/
 * brand-detail hiện không trả `filters`/`selected.brandIds` như
 * /_migration/catalog - thêm sidebar lọc thật cần sửa API PHP
 * (pts_v1_brand_detail.php) + contract, nằm ngoài phạm vi lượt sửa UI này
 * (tránh đụng Catalog/PDP lane đang làm song song). Ghi NEEDS VERIFICATION,
 * không tự thêm sidebar giả không có dữ liệu thật đứng sau.
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
        <SiteBreadcrumb :items="[...(data ? [{ label: data.brand.name }] : [])]" />
      </div>

      <div v-if="pending" class="mx-auto min-h-[45vh] w-full max-w-(--container-page) px-(--spacing-gutter) py-12" role="status" aria-live="polite">
        <p class="text-slate-500">Đang tải sản phẩm…</p>
      </div>

      <div v-else-if="error || !data" class="mx-auto min-h-[45vh] w-full max-w-(--container-page) px-(--spacing-gutter) py-12" role="status">
        <h1 class="mb-3 text-3xl font-black tracking-tight">Thương hiệu</h1>
        <p class="text-slate-500">Không tìm thấy thương hiệu hoặc dữ liệu PHP local chưa khả dụng. Preview không hiển thị dữ liệu giả.</p>
      </div>

      <div v-else class="mx-auto w-full max-w-(--container-page) px-(--spacing-gutter) pt-8 pb-16 max-ph:pt-6 max-ph:pb-12">
        <!-- H1 khớp PHP thật: trang brand KHÔNG đổi heading theo tên thương
             hiệu, giữ nguyên tiêu đề catalog chung (xem comment đầu file). -->
        <h1 class="mb-7 text-[clamp(28px,4vw,48px)] font-black leading-[1.15] tracking-tight">Sản phẩm năng lượng mặt trời</h1>

        <div class="mb-7 grid grid-cols-2 gap-4 border-b border-gray-200 pb-5 tb:grid-cols-4" aria-label="Lợi ích khi chọn sản phẩm">
          <div class="flex items-start gap-3"><span class="text-gold-700" aria-hidden="true">✦</span><span><strong class="block text-sm text-navy-950">Tư vấn lựa chọn</strong><small class="text-slate-500">Đúng nhu cầu, hiệu quả</small></span></div>
          <div class="flex items-start gap-3"><span class="text-gold-700" aria-hidden="true">⚙</span><span><strong class="block text-sm text-navy-950">Hỗ trợ kỹ thuật</strong><small class="text-slate-500">Trong suốt quá trình sử dụng</small></span></div>
          <div class="flex items-start gap-3"><span class="text-gold-700" aria-hidden="true">✓</span><span><strong class="block text-sm text-navy-950">Chính sách rõ ràng</strong><small class="text-slate-500">Minh bạch, dễ hiểu</small></span></div>
          <div class="flex items-start gap-3"><span class="text-gold-700" aria-hidden="true">↗</span><span><strong class="block text-sm text-navy-950">Giao hàng toàn quốc</strong><small class="text-slate-500">Nhanh chóng, an toàn</small></span></div>
        </div>

        <h2 class="mb-5 text-lg font-black text-navy-800">{{ data.brand.name }}</h2>

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

        <section class="relative isolate mt-10 grid grid-cols-1 items-center gap-6 overflow-hidden bg-navy-950 px-6 py-8 text-white tb:grid-cols-[minmax(0,1fr)_auto] nav:px-10 nav:py-9" aria-labelledby="brand-support-title">
          <div>
            <h2 id="brand-support-title" class="mb-2 text-2xl font-black">Cần hỗ trợ chọn sản phẩm phù hợp?</h2>
            <p class="mb-0 max-w-[720px] leading-relaxed text-blue-100">Chia sẻ nhu cầu để đội ngũ Phúc Thịnh Solar tư vấn cấu hình và báo giá phù hợp.</p>
          </div>
          <NuxtLink :to="migrationHref('/lien-he/')" class="inline-flex min-h-11 items-center justify-center bg-gold-500 px-5 font-extrabold text-navy-950 no-underline">Nhận tư vấn →</NuxtLink>
        </section>
      </div>
    </main>

    <SiteFooter :site="site" />
  </div>
</template>
