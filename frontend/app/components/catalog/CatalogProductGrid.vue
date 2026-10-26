<script setup lang="ts">
import type { PublicCatalogProduct } from '#shared/contracts/public-catalog'

defineProps<{ products: PublicCatalogProduct[] }>()
const failedImages = ref<Set<number>>(new Set())

function markImageFailed(productId: number) {
  failedImages.value = new Set(failedImages.value).add(productId)
}
</script>

<template>
  <div v-if="products.length" class="catalog-product-grid">
    <article v-for="product in products" :key="product.id" class="catalog-product">
      <NuxtLink external :to="product.url" class="catalog-product__media">
        <img v-if="product.media[0] && !failedImages.has(product.id)" :src="product.media[0]" :alt="product.name" loading="lazy" @error="markImageFailed(product.id)">
        <span v-else class="catalog-product__missing">Chưa có hình ảnh</span>
      </NuxtLink>
      <h3><NuxtLink external :to="product.url">{{ product.name }}</NuxtLink></h3>
      <p class="catalog-product__quote">Liên hệ để nhận báo giá</p>
    </article>
  </div>
  <p v-else class="catalog-empty" role="status">Chưa có sản phẩm trong danh mục này.</p>
</template>
