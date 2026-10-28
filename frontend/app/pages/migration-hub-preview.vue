<script setup lang="ts">
import type { PublicCatalogPayload } from '#shared/contracts/public-catalog'
import { isPublicCatalogPayload } from '#shared/contracts/public-catalog'
import { isPublicNewsPayload } from '#shared/contracts/public-news'
import { isPublicProjectsPayload } from '#shared/contracts/public-projects'
import { isPublicSolutionsPayload } from '#shared/contracts/public-solutions'
import { MIGRATION_PREVIEW_ROUTES } from '#shared/contracts/migration-preview-routes'
import { previewHref, phpHref } from '~/components/migration-hub/preview-links'

const labels = { preview: 'Bản xem thử', 'php-form': 'Form hoạt động trên PHP', migrating: 'Đang chuyển đổi' } as const
/** Màu badge theo trạng thái route — đúng 3 giá trị migration-hub-preview.css khai, không có ảnh production để đối chiếu vì đây là công cụ nội bộ. */
const statusClass: Record<keyof typeof labels, string> = {
  preview: 'bg-navy-50 text-navy-800',
  'php-form': 'bg-[#e7f2ec] text-[#185c38]',
  migrating: 'bg-[#fff3d6] text-[#74510e]',
}
const { data: catalogData, error: catalogError } = await useAsyncData<unknown>('migration-hub-catalog', () => $fetch('/_migration/catalog', { query: { per_page: '1' } }))
const catalog = computed(() => isPublicCatalogPayload(catalogData.value) ? catalogData.value : null)
const sampleProduct = computed(() => catalog.value?.products[0] ?? null)
const productHref = computed(() => sampleProduct.value ? previewHref('product', { slug: sampleProduct.value.slug }) : previewHref('product'))
const { data: newsData } = await useAsyncData<unknown>('migration-hub-news', () => $fetch('/_migration/news'))
const { data: projectsData } = await useAsyncData<unknown>('migration-hub-projects', () => $fetch('/_migration/projects'))
const { data: solutionsData } = await useAsyncData<unknown>('migration-hub-solutions', () => $fetch('/_migration/solutions'))
const sampleNews = computed(() => isPublicNewsPayload(newsData.value) ? (newsData.value.articles[0] ?? newsData.value.featured[0] ?? null) : null)
const sampleProject = computed(() => isPublicProjectsPayload(projectsData.value) ? (projectsData.value.projects[0] ?? projectsData.value.featured ?? null) : null)
const sampleSolution = computed(() => isPublicSolutionsPayload(solutionsData.value) ? (solutionsData.value.cards[0] ?? null) : null)

useSeoMeta({ title: 'Trung tâm xem thử Nuxt — Phúc Thịnh Solar', robots: 'noindex, nofollow, noarchive' })
</script>

<template>
  <!--
    Công cụ điều hướng nội bộ cho chính đợt chuyển đổi (noindex, không phải
    trang public) — không có ảnh production nào tương ứng để đối chiếu. Chuyển
    CSS thẳng sang Tailwind giữ nguyên giá trị gốc của migration-hub-preview.css.
  -->
  <div class="min-h-screen bg-white text-navy-800">
    <main class="px-4 pb-[72px] pt-12">
      <div class="mx-auto w-full max-w-[1120px]">
        <p class="mb-3 text-xs font-extrabold uppercase tracking-[.12em] text-gold-700">Nuxt migration preview</p>
        <h1 class="m-0 max-w-[760px] text-[clamp(32px,5vw,58px)] font-black leading-[1.08] tracking-tight">Trung tâm xem thử các trang đã chuyển đổi</h1>
        <p class="mb-12 mt-5 max-w-[720px] text-lg leading-relaxed text-slate-500 max-ph:mb-9 max-ph:text-base">
          Đi tới từng màn hình preview nội bộ để kiểm tra dữ liệu CMS và shell giao diện. Đây không phải điều hướng public và không thay đổi route PHP.
        </p>

        <section aria-labelledby="migration-routes-title">
          <h2 id="migration-routes-title" class="mb-5 text-[clamp(24px,3vw,32px)] font-black">Các đường dẫn xem thử</h2>
          <div class="grid grid-cols-1 gap-px border border-gray-200 bg-gray-200 tb:grid-cols-2">
            <article v-for="route in MIGRATION_PREVIEW_ROUTES" :key="route.key" class="flex min-h-[180px] min-w-0 flex-col justify-between gap-6 bg-white p-6 max-ph:min-h-0 max-ph:p-5">
              <div>
                <h3 class="mb-2 text-xl font-bold">{{ route.title }}</h3>
                <p class="text-slate-500 leading-relaxed">{{ route.description }}</p>
              </div>
              <div class="flex flex-wrap items-center gap-3">
                <a
                  class="inline-flex min-h-11 items-center rounded-chip border border-navy-800 bg-hdr-gold px-3.5 font-bold text-navy-950 no-underline focus-visible:outline focus-visible:outline-[3px] focus-visible:outline-gold-500 focus-visible:outline-offset-[3px]"
                  :href="previewHref(route.key)"
                >
                  Mở preview <span aria-hidden="true">→</span>
                </a>
                <span class="rounded-chip px-2 py-1.5 text-[13px] font-bold" :class="statusClass[route.status]">{{ labels[route.status] }}</span>
              </div>
            </article>
          </div>
        </section>

        <section class="mt-12 border-t border-gray-200 pt-8" aria-labelledby="migration-data-title">
          <h2 id="migration-data-title" class="mb-5 text-[clamp(24px,3vw,32px)] font-black">Mẫu dữ liệu CMS thật</h2>
          <p v-if="catalogError || !catalog" class="text-slate-500" role="status">Catalog local chưa khả dụng. Không hiển thị slug mẫu.</p>
          <template v-else>
            <p class="text-slate-500">Catalog trả về {{ catalog.pagination.total }} sản phẩm. Chỉ hiển thị mẫu khi CMS trả về slug hợp lệ.</p>
            <a v-if="sampleProduct" class="mt-3 inline-block font-bold text-navy-800 underline-offset-4 hover:underline focus-visible:outline focus-visible:outline-[3px] focus-visible:outline-gold-500 focus-visible:outline-offset-[3px]" :href="productHref">{{ sampleProduct.name }} — mở PDP preview <span aria-hidden="true">→</span></a>
            <p v-else class="text-slate-500" role="status">CMS chưa trả về sản phẩm để tạo liên kết PDP.</p>
          </template>
        </section>

        <section class="mt-12 border-t border-gray-200 pt-8" aria-labelledby="migration-detail-samples-title">
          <h2 id="migration-detail-samples-title" class="mb-5 text-[clamp(24px,3vw,32px)] font-black">Xem các trang chi tiết với dữ liệu CMS thật</h2>
          <p class="mb-5 text-slate-500">Đường dẫn mẫu được lấy từ API local, không phải bài viết hoặc dự án giả lập.</p>
          <div class="grid grid-cols-1 gap-px border border-gray-200 bg-gray-200 tb:grid-cols-2">
            <article class="flex min-h-[180px] min-w-0 flex-col justify-between gap-6 bg-white p-6 max-ph:min-h-0 max-ph:p-5">
              <h3 class="mb-2 text-xl font-bold">Bài viết chi tiết</h3>
              <a v-if="sampleNews" class="inline-flex min-h-11 items-center rounded-chip border border-navy-800 bg-hdr-gold px-3.5 font-bold text-navy-950 no-underline" :href="'/migration-news-detail-preview?slug=' + encodeURIComponent(sampleNews.slug)">{{ sampleNews.title }} →</a>
              <p v-else class="text-slate-500">Chưa có bài viết mẫu từ CMS.</p>
            </article>
            <article class="flex min-h-[180px] min-w-0 flex-col justify-between gap-6 bg-white p-6 max-ph:min-h-0 max-ph:p-5">
              <h3 class="mb-2 text-xl font-bold">Dự án chi tiết</h3>
              <a v-if="sampleProject" class="inline-flex min-h-11 items-center rounded-chip border border-navy-800 bg-hdr-gold px-3.5 font-bold text-navy-950 no-underline" :href="'/migration-project-detail-preview?slug=' + encodeURIComponent(sampleProject.slug)">{{ sampleProject.title }} →</a>
              <p v-else class="text-slate-500">Chưa có dự án mẫu từ CMS.</p>
            </article>
            <article class="flex min-h-[180px] min-w-0 flex-col justify-between gap-6 bg-white p-6 max-ph:min-h-0 max-ph:p-5">
              <h3 class="mb-2 text-xl font-bold">Giải pháp chi tiết</h3>
              <a v-if="sampleSolution" class="inline-flex min-h-11 items-center rounded-chip border border-navy-800 bg-hdr-gold px-3.5 font-bold text-navy-950 no-underline" :href="'/migration-solution-detail-preview?slug=' + encodeURIComponent(sampleSolution.slug)">{{ sampleSolution.title }} →</a>
              <p v-else class="text-slate-500">Chưa có giải pháp mẫu từ CMS.</p>
            </article>
          </div>
        </section>

        <aside class="mt-12 border-t border-gray-200 pt-8" aria-labelledby="migration-notice-title">
          <h2 id="migration-notice-title" class="mb-5 text-[clamp(24px,3vw,32px)] font-black">Lưu ý phạm vi</h2>
          <p class="mb-3 text-slate-500 leading-relaxed">Đây là preview local có noindex. Điều hướng chính, tìm kiếm, PDP, giỏ hàng và checkout đã được ánh xạ sang GUI Nuxt; PHP chỉ còn giữ các backend/session nghiệp vụ cần thiết qua fixed adapters.</p>
          <p class="text-slate-500 leading-relaxed">
            Form Liên hệ/Đại lý và các link Chính sách/Hỗ trợ trong footer đã có GUI Nuxt; PHP chỉ còn giữ validation/session/mutation phía server. PHP local vẫn có thể dùng để đối chiếu tại
            <a class="font-bold text-navy-800" :href="phpHref('contact')">http://localhost:8088</a>.
          </p>
        </aside>
      </div>
    </main>
  </div>
</template>
