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
const { data: catalogData, error: catalogError } = await useAsyncData<PublicCatalogPayload>(
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
const hasSidebar = computed(() => (siteData.value?.productCategoryTree?.length ?? 0) > 0)
const heading = computed(() => catalogData.value?.category && !Array.isArray(catalogData.value.category) ? catalogData.value.category.name : 'Danh mục sản phẩm')
const totalPages = computed(() => catalogData.value ? Math.max(1, Math.ceil(catalogData.value.pagination.total / catalogData.value.pagination.perPage)) : 1)
const currentPage = computed(() => catalogData.value?.pagination.page ?? 1)

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
    -->
    <main
      class="mx-auto grid w-full max-w-(--container-page) grid-cols-1 gap-6 px-(--spacing-gutter) pt-8 pb-16
             max-ph:pt-6 max-ph:pb-12"
      :class="hasSidebar ? 'nav:grid-cols-[192px_minmax(0,1fr)]' : ''"
    >
      <CatalogCategoryNav :categories="siteData?.productCategoryTree ?? []" :selected="selectedCategory?.url ?? ''" />

      <section aria-labelledby="catalog-preview-title">
        <p class="mb-5 text-slate-500">
          <NuxtLink class="text-inherit" to="/migration-catalog-preview">Danh mục sản phẩm</NuxtLink>
          <span v-if="selectedCategory"> / {{ selectedCategory.name }}</span>
        </p>

        <h1 id="catalog-preview-title" class="mb-3 text-[clamp(28px,4vw,48px)] font-black leading-[1.15] tracking-tight">
          {{ heading }}
        </h1>

        <p class="mb-7 leading-relaxed text-slate-500">
          Preview nội bộ từ dữ liệu CMS. Giá, bộ lọc, sắp xếp và tồn kho chưa được cung cấp trong Phase A.
        </p>

        <div v-if="catalogError" class="py-16" role="status">
          <h2 class="mb-2 text-xl font-extrabold">Không thể tải danh mục</h2>
          <p class="text-slate-500">Dữ liệu PHP local chưa khả dụng hoặc không khớp contract. Preview không hiển thị dữ liệu giả.</p>
        </div>

        <template v-else-if="catalogData">
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
            <template v-for="number in totalPages" :key="number">
              <span
                v-if="number === currentPage"
                class="inline-grid min-h-11 min-w-11 place-items-center rounded-chip border border-navy-800 bg-navy-800 px-3 py-2 text-white"
                aria-current="page"
              >
                {{ number }}
              </span>
              <NuxtLink
                v-else
                class="inline-grid min-h-11 min-w-11 place-items-center rounded-chip border border-gray-200 px-3 py-2 text-navy-800 no-underline transition-colors hover:border-navy-800"
                :to="pageLink(number)"
              >
                {{ number }}
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
