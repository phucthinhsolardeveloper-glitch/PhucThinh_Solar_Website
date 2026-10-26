<script setup lang="ts">
import type { PublicCatalogPayload } from '#shared/contracts/public-catalog'
import { isPublicCatalogPayload } from '#shared/contracts/public-catalog'
import { isPublicNewsPayload } from '#shared/contracts/public-news'
import { isPublicProjectsPayload } from '#shared/contracts/public-projects'
import { isPublicSolutionsPayload } from '#shared/contracts/public-solutions'
import { MIGRATION_PREVIEW_ROUTES } from '#shared/contracts/migration-preview-routes'
import { previewHref, phpHref } from '~/components/migration-hub/preview-links'

const labels = { preview: 'Bản xem thử', 'php-form': 'Form hoạt động trên PHP', migrating: 'Đang chuyển đổi' } as const
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
  <div class="migration-hub">
    <main class="migration-hub__main">
      <div class="migration-hub__wrap">
        <p class="migration-hub__context">Nuxt migration preview</p>
        <h1>Trung tâm xem thử các trang đã chuyển đổi</h1>
        <p class="migration-hub__lead">Đi tới từng màn hình preview nội bộ để kiểm tra dữ liệu CMS và shell giao diện. Đây không phải điều hướng public và không thay đổi route PHP.</p>

        <section aria-labelledby="migration-routes-title">
          <h2 id="migration-routes-title">Các đường dẫn xem thử</h2>
          <div class="migration-hub__routes">
            <article v-for="route in MIGRATION_PREVIEW_ROUTES" :key="route.key" class="migration-hub__route">
              <div>
                <h3>{{ route.title }}</h3>
                <p>{{ route.description }}</p>
              </div>
              <div class="migration-hub__actions">
                <a class="migration-hub__link migration-hub__link--primary" :href="previewHref(route.key)">Mở preview <span aria-hidden="true">→</span></a>
                <span class="migration-hub__status" :data-status="route.status">{{ labels[route.status] }}</span>
              </div>
            </article>
          </div>
        </section>

        <section class="migration-hub__data" aria-labelledby="migration-data-title">
          <h2 id="migration-data-title">Mẫu dữ liệu CMS thật</h2>
          <p v-if="catalogError || !catalog" role="status">Catalog local chưa khả dụng. Không hiển thị slug mẫu.</p>
          <template v-else>
            <p>Catalog trả về {{ catalog.pagination.total }} sản phẩm. Chỉ hiển thị mẫu khi CMS trả về slug hợp lệ.</p>
            <a v-if="sampleProduct" class="migration-hub__link" :href="productHref">{{ sampleProduct.name }} — mở PDP preview <span aria-hidden="true">→</span></a>
            <p v-else role="status">CMS chưa trả về sản phẩm để tạo liên kết PDP.</p>
          </template>
        </section>

        <section class="migration-hub__data" aria-labelledby="migration-detail-samples-title">
          <h2 id="migration-detail-samples-title">Xem các trang chi tiết với dữ liệu CMS thật</h2>
          <p>Đường dẫn mẫu được lấy từ API local, không phải bài viết hoặc dự án giả lập.</p>
          <div class="migration-hub__routes">
            <article class="migration-hub__route">
              <h3>Bài viết chi tiết</h3>
              <a v-if="sampleNews" class="migration-hub__link migration-hub__link--primary" :href="'/migration-news-detail-preview?slug=' + encodeURIComponent(sampleNews.slug)">{{ sampleNews.title }} →</a>
              <p v-else>Chưa có bài viết mẫu từ CMS.</p>
            </article>
            <article class="migration-hub__route">
              <h3>Dự án chi tiết</h3>
              <a v-if="sampleProject" class="migration-hub__link migration-hub__link--primary" :href="'/migration-project-detail-preview?slug=' + encodeURIComponent(sampleProject.slug)">{{ sampleProject.title }} →</a>
              <p v-else>Chưa có dự án mẫu từ CMS.</p>
            </article>
            <article class="migration-hub__route">
              <h3>Giải pháp chi tiết</h3>
              <a v-if="sampleSolution" class="migration-hub__link migration-hub__link--primary" :href="'/migration-solution-detail-preview?slug=' + encodeURIComponent(sampleSolution.slug)">{{ sampleSolution.title }} →</a>
              <p v-else>Chưa có giải pháp mẫu từ CMS.</p>
            </article>
          </div>
        </section>

        <aside class="migration-hub__notice" aria-labelledby="migration-notice-title">
          <h2 id="migration-notice-title">Lưu ý phạm vi</h2>
          <p>Đây là preview local có noindex. Một số liên kết public bên trong preview có thể 404 trên cổng :3100 cho tới khi hybrid routing được triển khai.</p>
          <p>Form liên hệ và đăng ký đại lý vẫn thuộc PHP. Mở trang PHP local riêng tại <a :href="phpHref('contact')">http://localhost:8088</a>.</p>
        </aside>
      </div>
    </main>
  </div>
</template>

<style>
@import '~/assets/css/migration-hub-preview.css';
</style>

