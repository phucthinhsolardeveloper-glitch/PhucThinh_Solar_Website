<script setup lang="ts">
import type { PublicProductDetailPayload } from '#shared/contracts/public-product-detail'
import { isPublicProductDetailPayload } from '#shared/contracts/public-product-detail'
import type { PublicSitePayload } from '#shared/contracts/public-site'
import { isPublicSitePayload } from '#shared/contracts/public-site'
import ProductPreviewPage from '~/components/product-preview/ProductPreviewPage.vue'

const fallbackSite: PublicSitePayload = { schemaVersion: 1, brand: { name: 'Phúc Thịnh Solar', hotline: '' }, productCategories: [] }
const route = useRoute()
const slug = computed(() => typeof route.query.slug === 'string' ? route.query.slug : '')
const { data: siteData } = await useFetch<unknown>('/_migration/site', { default: () => fallbackSite })
const { data: productData, error } = await useFetch<unknown>('/_migration/product-detail', { query: computed(() => ({ slug: slug.value })), default: () => null })
const site = computed(() => isPublicSitePayload(siteData.value) ? siteData.value : fallbackSite)
const product = computed<PublicProductDetailPayload['product'] | null>(() => isPublicProductDetailPayload(productData.value) ? productData.value.product : null)
useSeoMeta({ title: computed(() => product.value ? `${product.value.name} — preview` : 'Product preview'), robots: 'noindex, nofollow, noarchive' })
</script>
<template>
  <div class="migration-product-preview"><SiteHeader :site="site" /><main class="pts-container"><SiteBreadcrumb :items="[{ label: 'Sản phẩm preview' }, ...(product ? [{ label: product.name }] : [])]" /><div v-if="error || !product" class="product-preview-fallback" role="status"><h1>Product detail preview</h1><p>Không thể tải sản phẩm CMS hoặc slug chưa hợp lệ. Preview không hiển thị dữ liệu giả.</p></div><ProductPreviewPage v-else :product="product" /></main><SiteFooter :site="site" /></div>
</template>
<style>@import '~/assets/css/product-detail-preview.css'; .product-preview-fallback { min-height: 45vh; padding: 48px 0 80px; } .product-preview-fallback p { color: var(--pts-muted); }</style>
