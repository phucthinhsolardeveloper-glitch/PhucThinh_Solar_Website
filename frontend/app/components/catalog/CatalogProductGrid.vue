<script setup lang="ts">
import type { PublicCatalogProduct } from '#shared/contracts/public-catalog'

defineProps<{ products: PublicCatalogProduct[] }>()

function hideBrokenImage(event: Event) {
  const image = event.currentTarget
  if (image instanceof HTMLImageElement) image.hidden = true
}
</script>

<template>
  <div v-if="products.length" class="catalog-product-grid">
    <article v-for="product in products" :key="product.id" class="catalog-product">
      <NuxtLink external :to="product.url" class="catalog-product__media">
        <img v-if="product.media[0]" :src="product.media[0]" :alt="product.name" loading="lazy" @error="hideBrokenImage">
        <span v-else class="catalog-product__missing">Chưa có hình ảnh</span>
      </NuxtLink>
      <h3><NuxtLink external :to="product.url">{{ product.name }}</NuxtLink></h3>
      <p class="catalog-product__quote">Liên hệ để nhận báo giá</p>
    </article>
  </div>
  <p v-else class="catalog-empty" role="status">Chưa có sản phẩm trong danh mục này.</p>
</template>
