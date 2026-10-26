<script setup lang="ts">
import type { PublicCatalogPayload } from '#shared/contracts/public-catalog'
import type { PublicSitePayload, PublicProductCategoryNode } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import CatalogCategoryNav from '~/components/catalog/CatalogCategoryNav.vue'
import CatalogProductGrid from '~/components/catalog/CatalogProductGrid.vue'
import SiteHeader from '~/components/SiteHeader.vue'
import SiteFooter from '~/components/SiteFooter.vue'

const route = useRoute()
const category = computed(() => typeof route.query.category === 'string' ? route.query.category : '')
const page = computed(() => typeof route.query.page === 'string' ? route.query.page : '')
const perPage = computed(() => typeof route.query.per_page === 'string' ? route.query.per_page : '')
const query = computed(() => ({ ...(category.value ? { category: category.value } : {}), ...(page.value ? { page: page.value } : {}), ...(perPage.value ? { per_page: perPage.value } : {}) }))
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
const heading = computed(() => catalogData.value?.category && !Array.isArray(catalogData.value.category) ? catalogData.value.category.name : 'Danh mục sản phẩm')
const totalPages = computed(() => catalogData.value ? Math.max(1, Math.ceil(catalogData.value.pagination.total / catalogData.value.pagination.perPage)) : 1)
const currentPage = computed(() => catalogData.value?.pagination.page ?? 1)

function pageLink(nextPage: number) {
  return { path: '/migration-catalog-preview', query: { ...query.value, page: String(nextPage) } }
}

useSeoMeta({ title: 'Danh mục sản phẩm — internal preview', robots: 'noindex, nofollow, noarchive' })
</script>

<template>
  <div class="catalog-preview">
    <SiteHeader v-if="siteData" :site="siteData" />
    <main class="pts-container catalog-preview__inner">
      <CatalogCategoryNav :categories="siteData?.productCategoryTree ?? []" :selected="selectedCategory?.url ?? ''" />
      <section aria-labelledby="catalog-preview-title">
        <p class="pts-breadcrumb"><NuxtLink to="/migration-catalog-preview">Danh mục sản phẩm</NuxtLink><span v-if="selectedCategory"> / {{ selectedCategory.name }}</span></p>
        <h1 id="catalog-preview-title">{{ heading }}</h1>
        <p class="catalog-preview__note">Preview nội bộ từ dữ liệu CMS. Giá, bộ lọc, sắp xếp và tồn kho chưa được cung cấp trong Phase A.</p>
        <div v-if="catalogPending" class="catalog-loading" role="status" aria-live="polite"><p>Đang tải sản phẩm…</p></div>
        <div v-else-if="catalogError" class="catalog-error" role="status"><h2>Không thể tải danh mục</h2><p>Dữ liệu PHP local chưa khả dụng hoặc không khớp contract. Preview không hiển thị dữ liệu giả.</p></div>
        <template v-else-if="catalogData">
          <CatalogProductGrid :products="catalogData.products" />
          <nav v-if="totalPages > 1" class="catalog-pagination" aria-label="Phân trang sản phẩm">
            <NuxtLink v-if="currentPage > 1" :to="pageLink(currentPage - 1)">Trước</NuxtLink>
            <template v-for="number in totalPages" :key="number">
              <span v-if="number === currentPage" class="is-current" aria-current="page">{{ number }}</span>
              <NuxtLink v-else :to="pageLink(number)">{{ number }}</NuxtLink>
            </template>
            <NuxtLink v-if="currentPage < totalPages" :to="pageLink(currentPage + 1)">Sau</NuxtLink>
          </nav>
        </template>
      </section>
    </main>
    <SiteFooter v-if="siteData" :site="siteData" />
  </div>
</template>

<style>
@import '~/assets/css/catalog-preview.css';
.pts-breadcrumb { margin: 0 0 20px; color: var(--pts-muted); }
.pts-breadcrumb a { color: inherit; }
</style>
